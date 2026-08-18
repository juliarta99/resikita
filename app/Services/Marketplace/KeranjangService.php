<?php

declare(strict_types=1);

namespace App\Services\Marketplace;

use App\Exceptions\AturanBisnisException;
use App\Models\Keranjang;
use App\Models\Produk;
use App\Models\Umkm;
use App\Models\User;
use App\Services\Integration\ShippingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Keranjang belanja marketplace.
 *
 * Keranjang boleh berisi produk dari beberapa UMKM sekaligus, tapi
 * checkout memecahnya menjadi satu pesanan per toko, lihat
 * CheckoutService. Itulah sebabnya `isiTerkelompok()` ada: antarmuka
 * perlu menunjukkan pemecahan itu sebelum pengguna menekan bayar,
 * bukan mengejutkannya setelahnya dengan tiga tagihan ongkir.
 */
class KeranjangService
{
    public function __construct(
        private readonly ShippingService $shipping,
    ) {}

    /** Tambahkan produk; menambah produk yang sama menaikkan qty. */
    public function tambah(User $user, Produk $produk, int $qty = 1): Keranjang
    {
        if ($qty < 1) {
            throw AturanBisnisException::karena('Jumlah produk minimal satu.');
        }

        $this->pastikanBisaDibeli($produk);

        return DB::transaction(function () use ($user, $produk, $qty): Keranjang {
            $baris = Keranjang::query()
                ->where('user_id', $user->id)
                ->where('produk_id', $produk->id)
                ->lockForUpdate()
                ->first();

            $qtyBaru = ($baris?->qty ?? 0) + $qty;

            if (! $produk->stokCukup($qtyBaru)) {
                throw AturanBisnisException::karena(sprintf(
                    'Stok "%s" tinggal %d.',
                    $produk->nama,
                    $produk->stok,
                ));
            }

            if ($baris === null) {
                return Keranjang::create([
                    'user_id' => $user->id,
                    'produk_id' => $produk->id,
                    'qty' => $qtyBaru,
                ]);
            }

            $baris->update(['qty' => $qtyBaru]);

            return $baris->fresh();
        });
    }

    /** Setel qty ke nilai tertentu; nol berarti hapus dari keranjang. */
    public function ubahQty(User $user, Produk $produk, int $qty): ?Keranjang
    {
        if ($qty < 0) {
            throw AturanBisnisException::karena('Jumlah produk tidak boleh negatif.');
        }

        if ($qty === 0) {
            $this->hapus($user, $produk);

            return null;
        }

        $this->pastikanBisaDibeli($produk);

        if (! $produk->stokCukup($qty)) {
            throw AturanBisnisException::karena(sprintf(
                'Stok "%s" tinggal %d.',
                $produk->nama,
                $produk->stok,
            ));
        }

        $baris = Keranjang::query()
            ->where('user_id', $user->id)
            ->where('produk_id', $produk->id)
            ->first();

        if ($baris === null) {
            return $this->tambah($user, $produk, $qty);
        }

        $baris->update(['qty' => $qty]);

        return $baris->fresh();
    }

    public function hapus(User $user, Produk $produk): void
    {
        Keranjang::query()
            ->where('user_id', $user->id)
            ->where('produk_id', $produk->id)
            ->delete();
    }

    /**
     * Kosongkan keranjang, seluruhnya atau sebagian.
     *
     * Checkout boleh mengambil sebagian toko saja, jadi pengosongan pun
     * harus bisa sebagian. Menghapus seluruh keranjang setelah checkout
     * satu toko akan membuang barang yang sengaja ditinggalkan pembeli
     * untuk dibeli nanti, kerugian yang tidak bisa dibatalkan.
     *
     * @param  array<int, int>|null  $umkmIds  Hanya baris milik toko ini; null berarti seluruh keranjang.
     */
    public function kosongkan(User $user, ?array $umkmIds = null): void
    {
        $query = Keranjang::query()->where('user_id', $user->id);

        if ($umkmIds !== null) {
            $query->whereHas(
                'produk',
                fn (Builder $produk): Builder => $produk->whereIn('umkm_id', $umkmIds),
            );
        }

        $query->delete();
    }

    /**
     * Isi keranjang lengkap dengan relasi yang dipakai kartu produk.
     *
     * @return Collection<int, Keranjang>
     */
    public function isi(User $user): Collection
    {
        return Keranjang::query()
            ->where('user_id', $user->id)
            ->with(['produk.umkm', 'produk.fotoUtama'])
            ->get();
    }

    /**
     * Isi keranjang dikelompokkan per UMKM, sesuai bentuk pesanan nanti.
     *
     * @param  array<int, int>|null  $umkmIds  Batasi ke toko tertentu; null berarti seluruh keranjang.
     * @return Collection<int, array{umkm: Umkm, item: Collection<int, Keranjang>, subtotal: int, berat_gram: int}>
     */
    public function isiTerkelompok(User $user, ?array $umkmIds = null): Collection
    {
        $terpilih = $umkmIds === null ? null : array_map(intval(...), $umkmIds);

        return $this->isi($user)
            ->when(
                $terpilih !== null,
                fn (Collection $isi): Collection => $isi->filter(
                    fn (Keranjang $baris): bool => in_array($baris->produk->umkm_id, $terpilih, true),
                ),
            )
            ->groupBy(fn (Keranjang $baris): int => $baris->produk->umkm_id)
            ->map(fn (Collection $item): array => [
                'umkm' => $item->first()->produk->umkm,
                'item' => $item->values(),
                'subtotal' => $item->sum(fn (Keranjang $baris): int => $baris->subtotal()),
                'berat_gram' => $item->sum(fn (Keranjang $baris): int => $baris->produk->berat_gram * $baris->qty),
            ])
            ->values();
    }

    public function jumlahItem(User $user): int
    {
        return (int) Keranjang::query()->where('user_id', $user->id)->sum('qty');
    }

    /**
     * Buang baris yang produknya sudah tidak bisa dibeli.
     *
     * Produk bisa dinonaktifkan atau kehabisan stok setelah masuk
     * keranjang. Dipanggil sebelum halaman checkout ditampilkan supaya
     * pengguna melihat keranjang yang sudah bersih, bukan gagal di
     * langkah pembayaran.
     *
     * `$umkmIds` membatasi pemeriksaan ke toko yang benar-benar hendak
     * dibayar. Keadaan barang di toko lain belum relevan: menahannya
     * ikut diperiksa berarti sekaleng kompos yang kehabisan stok bisa
     * membatalkan pembelian tas rajut yang sudah siap dibayar. Toko yang
     * dilewati tetap dibersihkan saat keranjang dibuka utuh.
     *
     * @param  array<int, int>|null  $umkmIds  null berarti seluruh keranjang.
     * @return array<int, string> Nama produk yang dikeluarkan.
     */
    public function bersihkanYangTidakTersedia(User $user, ?array $umkmIds = null): array
    {
        $terpilih = $umkmIds === null ? null : array_map(intval(...), $umkmIds);
        $dikeluarkan = [];

        foreach ($this->isi($user) as $baris) {
            $produk = $baris->produk;

            // Baris tanpa produk adalah sampah, tidak bisa dipesan, dan
            // tidak punya toko yang bisa dicocokkan dengan penyaring.
            // Selalu dihapus, tapi hanya dilaporkan pada pembersihan
            // menyeluruh: membatalkan checkout toko lain gara-gara baris
            // yatim ini menghukum pembeli atas kerusakan yang bukan
            // bagian dari belanjaannya.
            if ($produk === null) {
                $baris->delete();

                if ($terpilih === null) {
                    $dikeluarkan[] = 'Produk yang sudah dihapus';
                }

                continue;
            }

            if ($terpilih !== null && ! in_array($produk->umkm_id, $terpilih, true)) {
                continue;
            }

            if (! $produk->is_active || ! $produk->umkm->status->bolehBerjualan()) {
                $dikeluarkan[] = $produk->nama;
                $baris->delete();

                continue;
            }

            // Toko bisa kehilangan asal pengirimannya setelah barangnya
            // masuk keranjang. Dibiarkan lewat, kegagalannya baru muncul
            // saat tarif dihitung, setelah pembeli mengisi alamat.
            if (! $this->shipping->punyaOrigin($produk->umkm)) {
                $dikeluarkan[] = $produk->nama;
                $baris->delete();

                continue;
            }

            if ($produk->stok === 0) {
                $dikeluarkan[] = $produk->nama;
                $baris->delete();

                continue;
            }

            // Stok berkurang setelah masuk keranjang: qty diturunkan
            // mengikuti sisa stok, bukan barisnya dibuang seluruhnya.
            if ($baris->qty > $produk->stok) {
                $baris->update(['qty' => $produk->stok]);
            }
        }

        return $dikeluarkan;
    }

    private function pastikanBisaDibeli(Produk $produk): void
    {
        if (! $produk->is_active) {
            throw AturanBisnisException::karena("Produk \"{$produk->nama}\" sedang tidak dijual.");
        }

        $produk->loadMissing('umkm');

        if (! $produk->umkm->status->bolehBerjualan()) {
            throw AturanBisnisException::karena(
                "Toko \"{$produk->umkm->nama}\" sedang tidak aktif.",
            );
        }

        // Ditolak di sini, bukan nanti saat menghitung ongkir. Toko tanpa
        // asal pengiriman akan gagal di langkah terakhir checkout, tempat
        // paling buruk untuk memberi tahu pembeli bahwa barangnya memang
        // tidak pernah bisa dikirim.
        if (! $this->shipping->punyaOrigin($produk->umkm)) {
            throw AturanBisnisException::karena(sprintf(
                'Toko "%s" belum menetapkan alamat asal pengiriman, jadi produknya belum '
                .'bisa dibeli. Coba lagi nanti.',
                $produk->umkm->nama,
            ));
        }

        if ($produk->stok === 0) {
            throw AturanBisnisException::karena("Stok \"{$produk->nama}\" sedang habis.");
        }
    }
}
