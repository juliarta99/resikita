<?php

declare(strict_types=1);

namespace App\Services\Integration;

use App\Exceptions\AturanBisnisException;
use App\Models\Umkm;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Ongkos kirim domestik lewat RajaOngkir V2 (Komerce).
 *
 * Dulu bernama RajaOngkirService. Namanya dinetralkan agar penyedia bisa
 * diganti tanpa mengubah nama kelas yang dirujuk di mana-mana, nama
 * kelas seharusnya menyebut apa yang dikerjakan, bukan vendor yang
 * kebetulan dipakai sekarang.
 */
class ShippingService
{
    /** Kurir yang ditawarkan secara baku. */
    private const KURIR_BAKU = 'jne:jnt:sicepat:pos';

    /**
     * Cari id wilayah tujuan dari kata kunci.
     *
     * Hasilnya di-cache karena daftar wilayah tujuan praktis tidak
     * berubah, sementara setiap pengguna yang mengetik alamat akan
     * memicu pencarian yang sama berulang kali.
     *
     * @return array<int, array<string, mixed>>
     */
    public function cariTujuan(string $kataKunci, int $batas = 10): array
    {
        $kataKunci = trim($kataKunci);

        if (mb_strlen($kataKunci) < 3) {
            throw AturanBisnisException::karena('Kata kunci pencarian minimal 3 huruf.');
        }

        $kunciCache = 'ongkir:tujuan:'.md5(mb_strtolower($kataKunci).":$batas");

        return Cache::remember($kunciCache, now()->addDay(), function () use ($kataKunci, $batas): array {
            $response = $this->client()->get('/destination/domestic-destination', [
                'search' => $kataKunci,
                'limit' => $batas,
                'offset' => 0,
            ]);

            if ($response->failed()) {
                Log::error('RajaOngkir gagal mencari tujuan', [
                    'kata_kunci' => $kataKunci,
                    'status' => $response->status(),
                ]);

                throw AturanBisnisException::karena(
                    'Layanan pencarian alamat sedang tidak tersedia. Coba lagi beberapa saat lagi.',
                    503,
                );
            }

            return $response->json('data', []);
        });
    }

    /**
     * Titik asal pengiriman milik satu toko.
     *
     * Asal pengiriman adalah milik penjual, bukan milik platform. Pada
     * marketplace banyak penjual, satu titik asal global akan menagih
     * pembeli untuk jarak yang tidak pernah ditempuh paketnya.
     *
     * Nilai config lama dipertahankan sebagai jaring pengaman untuk toko
     * yang terdaftar sebelum kolomnya ada. Itu keliru secara tarif, tapi
     * lebih ringan daripada membuat toko-toko itu berhenti menerima
     * pesanan tanpa pemberitahuan.
     *
     * Jaring itu tidak dijadikan alasan untuk membiarkan toko berjualan
     * tanpa asal: `punyaOrigin()` dipakai menutup jalurnya lebih awal,
     * saat produk diaktifkan dan saat barang dimasukkan ke keranjang,
     * supaya kegagalan tidak muncul pertama kali di depan pembeli yang
     * sudah siap membayar.
     */
    public function originUntuk(Umkm $umkm): int
    {
        $asal = $this->originAtauNol($umkm);

        if ($asal === 0) {
            throw AturanBisnisException::karena(sprintf(
                'Toko "%s" belum menetapkan alamat asal pengiriman, jadi ongkos kirimnya '
                .'belum bisa dihitung. Hubungi penjualnya.',
                $umkm->nama,
            ), 503);
        }

        return $asal;
    }

    /**
     * Apakah toko ini sudah punya asal pengiriman yang bisa dipakai.
     *
     * Sengaja satu method, bukan pemeriksaan `destination_id !== null`
     * yang disalin ke mana-mana: yang menentukan bukan hanya kolomnya,
     * tapi juga cadangan config. Menyalin pemeriksaannya berarti suatu
     * saat ada tempat yang lupa ikut memperhitungkan cadangan itu, lalu
     * menolak toko yang sebenarnya masih bisa mengirim.
     */
    public function punyaOrigin(Umkm $umkm): bool
    {
        return $this->originAtauNol($umkm) > 0;
    }

    /**
     * Asal pengiriman satu toko dalam bentuk siap tampil.
     *
     * @return array{destination_id: int|null, alamat: string|null}
     */
    public function asal(Umkm $umkm): array
    {
        $id = $this->originAtauNol($umkm);

        return [
            'destination_id' => $id > 0 ? $id : null,
            'alamat' => $umkm->alamat_asal,
        ];
    }

    /**
     * Tegakkan kesiapan kirim sebelum toko boleh menawarkan barang.
     *
     * Pesannya ditujukan ke penjual, bukan ke pembeli, ini dipanggil
     * dari panel UMKM, tempat orang yang membacanya memang bisa langsung
     * memperbaikinya.
     */
    public function pastikanSiapMengirim(Umkm $umkm): void
    {
        if ($this->punyaOrigin($umkm)) {
            return;
        }

        throw AturanBisnisException::karena(
            'Alamat asal pengiriman toko Anda belum ditetapkan, sehingga ongkos kirim '
            .'tidak bisa dihitung dan produk belum boleh tampil di marketplace. '
            .'Tetapkan lebih dulu di halaman Toko.',
        );
    }

    private function originAtauNol(Umkm $umkm): int
    {
        return (int) ($umkm->destination_id ?? config('services.rajaongkir.origin_id'));
    }

    /**
     * Hitung pilihan ongkos kirim.
     *
     * @param  int  $originId  Id wilayah asal, dari toko pengirim.
     * @param  int  $beratGram  Berat total paket dalam gram.
     * @return array<int, array{kurir: string, layanan: string, deskripsi: string, biaya: int, estimasi: string}>
     */
    public function hitung(int $originId, int $destinationId, int $beratGram, ?string $kurir = null): array
    {
        if ($originId <= 0) {
            throw AturanBisnisException::karena(
                'Alamat asal pengiriman belum ditetapkan untuk toko ini.',
                503,
            );
        }

        // Kurir menagih minimal satu kilogram. Mengirim berat di bawah
        // itu apa adanya membuat penyedia menolak permintaan.
        $berat = max(1000, $beratGram);

        $response = $this->client()
            ->asForm()
            ->post('/calculate/domestic-cost', [
                'origin' => $originId,
                'destination' => $destinationId,
                'weight' => $berat,
                'courier' => $kurir ?? self::KURIR_BAKU,
                'price' => 'lowest',
            ]);

        if ($response->failed()) {
            Log::error('RajaOngkir gagal menghitung ongkir', [
                'origin' => $originId,
                'destination' => $destinationId,
                'berat' => $berat,
                'status' => $response->status(),
            ]);

            throw AturanBisnisException::karena(
                'Layanan ongkos kirim sedang tidak tersedia. Coba lagi beberapa saat lagi.',
                503,
            );
        }

        return $this->normalisasi($response->json('data', []));
    }

    /**
     * Ambil satu pilihan ongkir dan pastikan biayanya masih sama.
     *
     * Dipanggil ulang saat checkout. Tarif yang ditampilkan bisa sudah
     * berubah sejak pengguna melihatnya, dan menyimpan angka lama berarti
     * selisihnya ditanggung entah oleh siapa.
     *
     * @return array{kurir: string, layanan: string, deskripsi: string, biaya: int, estimasi: string}
     */
    public function ambilLayanan(int $originId, int $destinationId, int $beratGram, string $kurir, string $layanan): array
    {
        $pilihan = $this->hitung($originId, $destinationId, $beratGram, $kurir);

        foreach ($pilihan as $opsi) {
            if ($opsi['kurir'] === $kurir && $opsi['layanan'] === $layanan) {
                return $opsi;
            }
        }

        throw AturanBisnisException::karena(
            'Layanan pengiriman yang dipilih sudah tidak tersedia. Silakan pilih ulang.',
        );
    }

    /**
     * Seragamkan bentuk data dari penyedia.
     *
     * Response mentah RajaOngkir memakai nama kunci yang berbeda antar
     * endpoint. Menormalkannya di sini membuat sisa aplikasi tidak perlu
     * tahu bentuk aslinya.
     *
     * @param  array<int, array<string, mixed>>  $data
     * @return array<int, array{kurir: string, layanan: string, deskripsi: string, biaya: int, estimasi: string}>
     */
    private function normalisasi(array $data): array
    {
        $hasil = [];

        foreach ($data as $baris) {
            $biaya = (int) ($baris['cost'] ?? 0);

            if ($biaya <= 0) {
                continue;
            }

            $hasil[] = [
                'kurir' => (string) ($baris['code'] ?? ''),
                'layanan' => (string) ($baris['service'] ?? ''),
                'deskripsi' => (string) ($baris['description'] ?? ''),
                'biaya' => $biaya,
                'estimasi' => (string) ($baris['etd'] ?? '-'),
            ];
        }

        usort($hasil, static fn (array $a, array $b): int => $a['biaya'] <=> $b['biaya']);

        return $hasil;
    }

    private function client()
    {
        return Http::withHeaders([
            'key' => (string) config('services.rajaongkir.key'),
            'Accept' => 'application/json',
        ])
            ->timeout(20)
            ->baseUrl((string) config('services.rajaongkir.base_url'));
    }
}
