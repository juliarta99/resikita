<?php

declare(strict_types=1);

namespace App\Services\Marketplace;

use App\Enums\StatusPembayaran;
use App\Enums\StatusPesanan;
use App\Enums\TipeTransaksiDompet;
use App\Exceptions\AturanBisnisException;
use App\Models\Pembayaran;
use App\Models\Pesanan;
use App\Models\Ulasan;
use App\Models\User;
use App\Services\Integration\MidtransService;
use App\Services\Wallet\UmkmDompetService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Daur hidup pesanan setelah checkout.
 *
 * ## Saldo penjual masuk saat pesanan selesai, bukan saat dibayar
 *
 * Uang pembeli ditahan platform selama barang dalam perjalanan.
 * Kalau saldo penjual dikredit begitu pembayaran lunas, pembatalan atau
 * paket yang tidak pernah sampai berarti menarik kembali uang yang
 * mungkin sudah ditarik ke rekening. Menahannya sampai pembeli
 * mengonfirmasi penerimaan membuat pengembalian dana selalu mungkin.
 */
class PesananService
{
    public function __construct(
        private readonly UmkmDompetService $umkmDompet,
        private readonly MidtransService $midtrans,
        private readonly CheckoutService $checkout,
    ) {}

    /**
     * Terapkan callback pembayaran dari Midtrans.
     *
     * Aman dipanggil berulang: Midtrans mengirim notifikasi yang sama
     * lebih dari sekali, dan pesanan yang sudah lunas tidak boleh
     * berubah lagi karenanya.
     *
     * @param  array<string, mixed>  $payload
     */
    public function terapkanNotifikasiPembayaran(array $payload): ?Pesanan
    {
        $hasil = $this->midtrans->bacaNotifikasi($payload);

        $pesanan = Pesanan::where('kode', $hasil['order_id'])->first();

        if ($pesanan === null) {
            Log::warning('Notifikasi Midtrans untuk pesanan yang tidak dikenal', [
                'order_id' => $hasil['order_id'],
            ]);

            return null;
        }

        return DB::transaction(function () use ($pesanan, $hasil, $payload): Pesanan {
            /** @var Pembayaran|null $pembayaran */
            $pembayaran = $pesanan->pembayaran()->latest('id')->first();

            $pembayaran?->update([
                'midtrans_transaction_id' => $hasil['transaction_id'],
                'status' => $hasil['status'],
                'dibayar_at' => $hasil['status']->isLunas() ? now() : null,
                'raw_payload' => $payload,
            ]);

            if ($hasil['status']->isLunas() && $pesanan->status === StatusPesanan::MenungguBayar) {
                $pesanan->update([
                    'status' => StatusPesanan::Dibayar,
                    'dibayar_at' => now(),
                ]);
            }

            // Pembayaran gagal atau kedaluwarsa mengembalikan stok lewat
            // jalur pembatalan yang sama, supaya tidak ada dua tempat
            // yang mengurus pengembalian stok.
            if (
                in_array($hasil['status'], [StatusPembayaran::Failed, StatusPembayaran::Expired], true)
                && $pesanan->status === StatusPesanan::MenungguBayar
            ) {
                $this->checkout->batalkan($pesanan, 'Pembayaran '.$hasil['status']->label());
            }

            return $pesanan->fresh();
        });
    }

    /** Penjual menandai pesanan sedang dikemas. */
    public function tandaiDikemas(Pesanan $pesanan): Pesanan
    {
        $this->pastikanTransisiSah($pesanan, StatusPesanan::Dikemas);

        $pesanan->update(['status' => StatusPesanan::Dikemas]);

        return $pesanan->fresh();
    }

    /** Penjual mengirim paket dan mencatat nomor resi. */
    public function tandaiDikirim(Pesanan $pesanan, string $noResi): Pesanan
    {
        $this->pastikanTransisiSah($pesanan, StatusPesanan::Dikirim);

        if (trim($noResi) === '') {
            throw AturanBisnisException::karena('Nomor resi wajib diisi saat mengirim pesanan.');
        }

        $pesanan->update([
            'status' => StatusPesanan::Dikirim,
            'no_resi' => trim($noResi),
            'dikirim_at' => now(),
        ]);

        return $pesanan->fresh();
    }

    /**
     * Pembeli mengonfirmasi paket diterima.
     *
     * Di sinilah saldo penjual bertambah.
     */
    public function tandaiSelesai(Pesanan $pesanan): Pesanan
    {
        $this->pastikanTransisiSah($pesanan, StatusPesanan::Selesai);

        return DB::transaction(function () use ($pesanan): Pesanan {
            $pesanan->update([
                'status' => StatusPesanan::Selesai,
                'selesai_at' => now(),
            ]);

            // Penjual menerima subtotal, bukan total. Ongkir adalah hak
            // kurir dan tidak pernah menjadi pendapatan toko.
            $this->umkmDompet->kredit(
                $pesanan->umkm,
                $pesanan->subtotal,
                TipeTransaksiDompet::Setor,
                $pesanan,
                'Hasil penjualan pesanan '.$pesanan->kode,
            );

            return $pesanan->fresh();
        });
    }

    /**
     * Terbitkan ulang Snap token untuk pesanan yang belum dibayar.
     *
     * Token Midtrans punya masa berlaku; pembeli yang menutup halaman
     * pembayaran dan kembali sehari kemudian membutuhkan token baru,
     * bukan pesanan baru.
     */
    public function bayarUlang(Pesanan $pesanan): Pesanan
    {
        if ($pesanan->status !== StatusPesanan::MenungguBayar) {
            throw AturanBisnisException::karena(
                "Pesanan berstatus \"{$pesanan->status->label()}\" tidak perlu dibayar lagi.",
            );
        }

        if (! $pesanan->metode_bayar->butuhSnapToken()) {
            throw AturanBisnisException::karena('Pesanan ini tidak dibayar lewat Midtrans.');
        }

        $pesanan->loadMissing(['item', 'user']);

        $token = $this->midtrans->buatSnapToken(
            $pesanan->kode,
            $pesanan->total,
            [
                'first_name' => $pesanan->nama_penerima,
                'email' => $pesanan->user->email,
                'phone' => $pesanan->phone_penerima,
            ],
        );

        $pesanan->update(['snap_token' => $token]);

        return $pesanan->fresh();
    }

    /**
     * Tulis ulasan atas produk dalam sebuah pesanan.
     *
     * Terikat pesanan, sehingga hanya pembeli yang benar-benar
     * bertransaksi yang bisa mengulas.
     *
     * @param  array<string, mixed>  $data
     */
    public function tulisUlasan(Pesanan $pesanan, User $penulis, array $data): Ulasan
    {
        if ($pesanan->user_id !== $penulis->id) {
            throw AturanBisnisException::tidakBerwenang('Pesanan ini bukan milik Anda.');
        }

        if (! $pesanan->status->bisaDiulas()) {
            throw AturanBisnisException::karena(
                'Ulasan baru bisa ditulis setelah pesanan selesai.',
            );
        }

        $produkId = $data['produk_id'] ?? null;

        if ($produkId !== null && ! $pesanan->item->contains('produk_id', $produkId)) {
            throw AturanBisnisException::karena('Produk tersebut tidak ada dalam pesanan ini.');
        }

        $sudahAda = Ulasan::query()
            ->where('pesanan_id', $pesanan->id)
            ->where('produk_id', $produkId)
            ->exists();

        if ($sudahAda) {
            throw AturanBisnisException::karena('Anda sudah menulis ulasan untuk produk ini.');
        }

        return Ulasan::create([
            'user_id' => $penulis->id,
            'pesanan_id' => $pesanan->id,
            'produk_id' => $produkId,
            'umkm_id' => $pesanan->umkm_id,
            'rating' => (int) $data['rating'],
            'komentar' => $data['komentar'] ?? null,
            'foto_path' => $data['foto_path'] ?? null,
        ]);
    }

    private function pastikanTransisiSah(Pesanan $pesanan, StatusPesanan $tujuan): void
    {
        if (! $pesanan->status->bolehPindahKe($tujuan)) {
            throw AturanBisnisException::karena(sprintf(
                'Pesanan berstatus "%s" tidak bisa diubah menjadi "%s".',
                $pesanan->status->label(),
                $tujuan->label(),
            ));
        }
    }
}
