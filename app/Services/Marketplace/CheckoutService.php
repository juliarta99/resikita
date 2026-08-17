<?php

declare(strict_types=1);

namespace App\Services\Marketplace;

use App\Enums\MetodeBayar;
use App\Enums\StatusPembayaran;
use App\Enums\StatusPesanan;
use App\Enums\TipeTransaksiDompet;
use App\Exceptions\AturanBisnisException;
use App\Models\Keranjang;
use App\Models\Pembayaran;
use App\Models\Pesanan;
use App\Models\PesananItem;
use App\Models\Produk;
use App\Models\User;
use App\Services\Integration\MidtransService;
use App\Services\Integration\ShippingService;
use App\Services\Wallet\DompetService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Checkout keranjang menjadi pesanan.
 *
 * ## Satu pesanan hanya dari satu toko
 *
 * Keranjang boleh berisi produk dari beberapa UMKM, tapi checkout
 * memecahnya menjadi satu pesanan per toko. Bukan pilihan gaya:
 * ongkos kirim dihitung dari alamat masing-masing penjual, nomor resi
 * milik masing-masing paket, dan saldo hasil penjualan masuk ke dompet
 * masing-masing UMKM. Satu pesanan lintas toko akan membuat ketiganya
 * kehilangan pemilik yang jelas.
 *
 * ## Toko yang dibayar ditentukan kunci `pengiriman`
 *
 * Keranjang tidak harus habis dalam sekali checkout. Yang menentukan
 * toko mana yang dipesan adalah kunci pada `pengiriman`, bukan isi
 * keranjang, sehingga pembeli bisa membayar satu toko lebih dulu dan
 * meninggalkan sisanya. Mengirim seluruh toko menghasilkan perilaku
 * lama persis, jadi klien lama tidak perlu diubah.
 *
 * ## Stok dipotong saat checkout
 *
 * Bukan saat pembayaran lunas. Kalau menunggu lunas, dua pembeli bisa
 * sama-sama memesan barang terakhir dan salah satunya baru tahu setelah
 * membayar. Konsekuensinya, pembatalan wajib mengembalikan stok,
 * ditegakkan `StatusPesanan::perluKembalikanStok()`.
 *
 * ## Harga dan nama dibekukan
 *
 * `harga_snapshot` dan `nama_snapshot` mengunci keadaan produk pada
 * saat checkout. UMKM boleh mengubah harga kapan pun; nota yang sudah
 * terbit tidak boleh ikut berubah.
 */
class CheckoutService
{
    public function __construct(
        private readonly KeranjangService $keranjang,
        private readonly ShippingService $shipping,
        private readonly DompetService $dompet,
        private readonly MidtransService $midtrans,
    ) {}

    /**
     * Hitung rincian checkout tanpa menyimpan apa pun.
     *
     * Dipanggil halaman checkout untuk menampilkan pilihan ongkir per
     * toko sebelum pengguna memutuskan.
     *
     * `$umkmIds` menyaring ke toko yang benar-benar akan dibayar. Tanpa
     * penyaringan itu, memilih satu toko dari keranjang lintas toko tetap
     * menagih ongkir seluruh toko ke penyedia, lambat, dan angkanya
     * membingungkan karena tidak ada yang akan membayarnya.
     *
     * @param  array<int, int>|null  $umkmIds  null berarti seluruh keranjang.
     * @return Collection<int, array<string, mixed>>
     */
    public function pratinjau(User $user, int $destinationId, ?array $umkmIds = null): Collection
    {
        $this->keranjang->bersihkanYangTidakTersedia($user, $umkmIds);

        return $this->keranjang->isiTerkelompok($user, $umkmIds)->map(function (array $grup) use ($destinationId): array {
            $umkm = $grup['umkm'];

            return [
                ...$grup,
                'asal' => $this->shipping->asal($umkm),
                'pilihan_ongkir' => $this->shipping->hitung(
                    $this->shipping->originUntuk($umkm),
                    $destinationId,
                    $grup['berat_gram'],
                ),
            ];
        });
    }

    /**
     * Ubah keranjang menjadi pesanan.
     *
     * Kunci `pengiriman` menentukan toko mana yang dipesan; toko lain
     * dibiarkan di keranjang.
     *
     * @param  array<string, mixed>  $data  nama_penerima, phone_penerima, alamat_kirim, destination_id, metode_bayar, pengiriman[umkm_id => ['kurir','layanan']]
     * @return Collection<int, Pesanan>
     */
    public function checkout(User $user, array $data): Collection
    {
        // Kunci `pengiriman` adalah daftar toko yang di-checkout, dan
        // dibaca paling awal karena setiap langkah sesudahnya dibatasi
        // olehnya: pembersihan keranjang, perhitungan ongkir, pemeriksaan
        // saldo, sampai baris mana yang dihapus di akhir.
        //
        // Arah pemeriksaannya sengaja dibalik: dulu setiap toko di
        // keranjang wajib punya entri pengiriman, sekarang setiap entri
        // pengiriman wajib punya toko di keranjang. Pembalikan itulah
        // yang membuat checkout sebagian mungkin tanpa endpoint baru,
        // dan mengirim seluruh toko tetap menghasilkan perilaku lama.
        $pengiriman = [];

        foreach ($data['pengiriman'] ?? [] as $umkmId => $pilihan) {
            $pengiriman[(int) $umkmId] = $pilihan;
        }

        // Pembersihan dibatasi ke toko terpilih. Produk toko lain yang
        // kehabisan stok bukan urusan pembelian ini, dan membatalkan
        // checkout karenanya membuat barang di rak sebelah menyandera
        // transaksi yang sebenarnya sudah siap dibayar.
        $dikeluarkan = $this->keranjang->bersihkanYangTidakTersedia($user, array_keys($pengiriman));

        if ($dikeluarkan !== []) {
            throw AturanBisnisException::karena(
                'Beberapa produk tidak lagi tersedia dan sudah dikeluarkan dari keranjang: '
                .implode(', ', $dikeluarkan).'. Periksa kembali pesanan Anda.',
            );
        }

        $grup = $this->keranjang->isiTerkelompok($user)
            ->keyBy(fn (array $baris): int => $baris['umkm']->id);

        if ($grup->isEmpty()) {
            throw AturanBisnisException::karena('Keranjang Anda kosong.');
        }

        $metode = $data['metode_bayar'] instanceof MetodeBayar
            ? $data['metode_bayar']
            : MetodeBayar::from($data['metode_bayar']);

        $destinationId = (int) $data['destination_id'];

        if ($pengiriman === []) {
            throw AturanBisnisException::karena(
                'Pilihan pengiriman belum ditentukan. Pilih setidaknya satu toko untuk dipesan.',
            );
        }

        $asing = array_diff(array_keys($pengiriman), $grup->keys()->all());

        if ($asing !== []) {
            throw AturanBisnisException::karena(
                'Ada toko pada pilihan pengiriman yang tidak ada di keranjang Anda. '
                .'Muat ulang keranjang lalu pilih ulang.',
            );
        }

        $terpilih = $grup->only(array_keys($pengiriman));

        // Ongkir dihitung ulang di luar transaction. Panggilan HTTP ke
        // penyedia bisa lambat, dan menahan baris produk terkunci selama
        // menunggu jaringan akan memblokir pembeli lain.
        $ongkirPerToko = [];

        foreach ($terpilih as $umkmId => $baris) {
            $ongkirPerToko[$umkmId] = $this->shipping->ambilLayanan(
                $this->shipping->originUntuk($baris['umkm']),
                $destinationId,
                $baris['berat_gram'],
                $pengiriman[$umkmId]['kurir'],
                $pengiriman[$umkmId]['layanan'],
            );
        }

        $totalSeluruhnya = $terpilih->sum(
            fn (array $baris): int => $baris['subtotal'] + $ongkirPerToko[$baris['umkm']->id]['biaya'],
        );

        // Saldo diperiksa untuk seluruh toko terpilih sekaligus sebelum
        // satu pesanan pun dibuat. Tanpa itu, pesanan toko pertama bisa
        // berhasil lalu toko kedua gagal karena saldo habis, dan
        // pembeli menerima setengah belanjaan yang tidak diminta.
        //
        // Yang tidak ikut di-checkout tidak ikut dihitung: menagih saldo
        // untuk barang yang masih tertinggal di keranjang akan menolak
        // pembelian yang sebenarnya sanggup dibayar.
        if ($metode === MetodeBayar::Saldo && ! $this->dompet->cukup($user, $totalSeluruhnya)) {
            throw AturanBisnisException::karena(sprintf(
                'Saldo tidak mencukupi. Dibutuhkan Rp %s, saldo Anda Rp %s.',
                number_format($totalSeluruhnya, 0, ',', '.'),
                number_format($this->dompet->saldo($user), 0, ',', '.'),
            ));
        }

        $pesananDibuat = DB::transaction(function () use ($user, $terpilih, $data, $metode, $destinationId, $ongkirPerToko): Collection {
            $hasil = collect();

            foreach ($terpilih as $baris) {
                $hasil->push($this->buatPesananSatuToko(
                    $user,
                    $baris,
                    $data,
                    $metode,
                    $destinationId,
                    $ongkirPerToko[$baris['umkm']->id],
                ));
            }

            // Hanya baris milik toko yang benar-benar dipesan. Toko lain
            // tetap menunggu di keranjang.
            $this->keranjang->kosongkan($user, $terpilih->keys()->all());

            return $hasil;
        });

        // Snap token diminta setelah transaction ditutup. Memanggil
        // Midtrans di dalam transaction berarti menahan kunci baris
        // selama menunggu jaringan penyedia pembayaran.
        if ($metode === MetodeBayar::Midtrans) {
            $pesananDibuat->each(fn (Pesanan $pesanan) => $this->siapkanPembayaranMidtrans($pesanan));
        }

        return $pesananDibuat->map(fn (Pesanan $pesanan): Pesanan => $pesanan->fresh(['item', 'umkm']));
    }

    /**
     * Batalkan pesanan dan kembalikan apa yang sudah berpindah.
     */
    public function batalkan(Pesanan $pesanan, ?string $alasan = null): Pesanan
    {
        if (! $pesanan->status->bisaDibatalkanPembeli()) {
            throw AturanBisnisException::karena(
                "Pesanan berstatus \"{$pesanan->status->label()}\" tidak bisa dibatalkan.",
            );
        }

        return DB::transaction(function () use ($pesanan, $alasan): Pesanan {
            $statusLama = $pesanan->status;

            $pesanan->update(['status' => StatusPesanan::Dibatalkan]);

            // Stok dikembalikan karena dipotong sejak checkout.
            foreach ($pesanan->item as $item) {
                if ($item->produk_id !== null) {
                    Produk::whereKey($item->produk_id)->increment('stok', $item->qty);
                }
            }

            // Uang hanya dikembalikan kalau memang sudah dibayar.
            if ($statusLama === StatusPesanan::Dibayar) {
                $this->dompet->kredit(
                    $pesanan->user,
                    $pesanan->total,
                    TipeTransaksiDompet::Refund,
                    $pesanan,
                    'Pengembalian dana pesanan '.$pesanan->kode.($alasan !== null ? " ($alasan)" : ''),
                );
            }

            return $pesanan->fresh();
        });
    }

    /** Buat satu pesanan untuk satu toko. */
    private function buatPesananSatuToko(
        User $user,
        array $baris,
        array $data,
        MetodeBayar $metode,
        int $destinationId,
        array $ongkir,
    ): Pesanan {
        $umkm = $baris['umkm'];
        $subtotal = (int) $baris['subtotal'];
        $total = $subtotal + $ongkir['biaya'];

        $pesanan = Pesanan::create([
            'kode' => $this->kodePesanan(),
            'user_id' => $user->id,
            'umkm_id' => $umkm->id,
            'subtotal' => $subtotal,
            'ongkir' => $ongkir['biaya'],
            'total' => $total,
            'metode_bayar' => $metode,
            'status' => StatusPesanan::MenungguBayar,
            'nama_penerima' => $data['nama_penerima'],
            'phone_penerima' => $data['phone_penerima'],
            'alamat_kirim' => $data['alamat_kirim'],
            'destination_id' => $destinationId,
            'kurir' => $ongkir['kurir'],
            'layanan_kurir' => $ongkir['layanan'],
        ]);

        /** @var Collection<int, Keranjang> $itemKeranjang */
        $itemKeranjang = $baris['item'];

        foreach ($itemKeranjang as $isi) {
            $produk = Produk::whereKey($isi->produk_id)->lockForUpdate()->first();

            if ($produk === null || ! $produk->stokCukup($isi->qty)) {
                throw AturanBisnisException::karena(sprintf(
                    'Stok "%s" berubah saat proses checkout. Silakan periksa keranjang Anda.',
                    $isi->produk->nama,
                ));
            }

            PesananItem::create([
                'pesanan_id' => $pesanan->id,
                'produk_id' => $produk->id,
                'nama_snapshot' => $produk->nama,
                'harga_snapshot' => $produk->harga,
                'qty' => $isi->qty,
                'subtotal' => $produk->harga * $isi->qty,
            ]);

            $produk->decrement('stok', $isi->qty);
        }

        Pembayaran::create([
            'payable_type' => $pesanan->getMorphClass(),
            'payable_id' => $pesanan->id,
            'metode' => $metode->value,
            'jumlah' => $total,
            'status' => StatusPembayaran::Pending,
        ]);

        // Pembayaran saldo tuntas seketika; tidak ada callback yang
        // perlu ditunggu.
        if ($metode === MetodeBayar::Saldo) {
            $this->dompet->debit(
                $user,
                $total,
                TipeTransaksiDompet::Belanja,
                $pesanan,
                'Pembelian pesanan '.$pesanan->kode,
            );

            $pesanan->update([
                'status' => StatusPesanan::Dibayar,
                'dibayar_at' => now(),
            ]);

            $pesanan->pembayaran()->update([
                'status' => StatusPembayaran::Paid,
                'dibayar_at' => now(),
            ]);
        }

        return $pesanan;
    }

    /** Minta Snap token dan simpan ke pesanan. */
    private function siapkanPembayaranMidtrans(Pesanan $pesanan): void
    {
        $pesanan->loadMissing(['item', 'user']);

        $item = $pesanan->item
            ->map(fn (PesananItem $i): array => [
                'id' => (string) $i->produk_id,
                'price' => $i->harga_snapshot,
                'quantity' => $i->qty,
                'name' => Str::limit($i->nama_snapshot, 50, ''),
            ])
            ->all();

        if ($pesanan->ongkir > 0) {
            $item[] = [
                'id' => 'ONGKIR',
                'price' => $pesanan->ongkir,
                'quantity' => 1,
                'name' => 'Ongkos kirim '.Str::upper((string) $pesanan->kurir),
            ];
        }

        $token = $this->midtrans->buatSnapToken(
            $pesanan->kode,
            $pesanan->total,
            [
                'first_name' => $pesanan->nama_penerima,
                'email' => $pesanan->user->email,
                'phone' => $pesanan->phone_penerima,
            ],
            $item,
        );

        $pesanan->update(['snap_token' => $token]);

        $pesanan->pembayaran()->update(['midtrans_order_id' => $pesanan->kode]);
    }

    private function kodePesanan(): string
    {
        return 'RSK-ORD-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
    }
}
