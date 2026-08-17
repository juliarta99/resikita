<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\TingkatWilayah;
use App\Models\Wilayah;
use Illuminate\Database\Seeder;

/**
 * Hierarki wilayah administrasi nasional.
 *
 * ## Cakupan berkas ini
 *
 * **Seluruh 38 provinsi** disemai lengkap dengan kode Kemendagri dan
 * titik pusatnya, karena tanpa itu pemilih wilayah bertingkat di
 * halaman pengajuan tidak bisa dipakai sama sekali.
 *
 * **Kabupaten, kecamatan, dan desa hanya sebagian**, cukup untuk
 * menjalankan demo dan uji, bukan salinan lengkap. Data lengkapnya
 * ada 38 provinsi, 514 kabupaten/kota, lebih dari 7.000 kecamatan, dan
 * sekitar 84.000 desa. Menempelkan angka sebanyak itu ke dalam berkas
 * PHP adalah cara yang salah: ia berubah tiap kali ada pemekaran, dan
 * satu berkas raksasa yang diketik ulang manusia pasti mengandung salah
 * ketik yang tidak akan pernah ditemukan.
 *
 * Untuk data resmi yang lengkap, pakai:
 *
 *     php artisan wilayah:impor path/ke/wilayah.csv
 *
 * Perintah itu membaca berkas Kemendagri apa adanya dan bersifat
 * idempoten, sehingga pemutakhiran tahunan tinggal dijalankan ulang.
 *
 * ## Kenapa titik pusat ikut disemai
 *
 * WilayahResolverService mencari wilayah terdekat dari koordinat laporan
 * ketika titiknya tidak jatuh persis di wilayah mana pun. Tanpa
 * latitude/longitude, resolusi itu tidak punya apa pun untuk dibandingkan
 * dan setiap laporan berakhir di tangan fasilitator.
 */
class WilayahSeeder extends Seeder
{
    /**
     * 38 provinsi dengan kode Permendagri dan titik pusat perkiraan.
     *
     * @var array<int, array{0: string, 1: string, 2: float, 3: float}>
     */
    private const PROVINSI = [
        ['11', 'Aceh', 4.6951, 96.7494],
        ['12', 'Sumatera Utara', 2.1154, 99.5451],
        ['13', 'Sumatera Barat', -0.7399, 100.8000],
        ['14', 'Riau', 0.2933, 101.7068],
        ['15', 'Jambi', -1.6101, 103.6131],
        ['16', 'Sumatera Selatan', -3.3194, 103.9140],
        ['17', 'Bengkulu', -3.5778, 102.3464],
        ['18', 'Lampung', -4.5586, 105.4068],
        ['19', 'Kepulauan Bangka Belitung', -2.7411, 106.4406],
        ['21', 'Kepulauan Riau', 3.9457, 108.1429],
        ['31', 'DKI Jakarta', -6.2088, 106.8456],
        ['32', 'Jawa Barat', -6.9147, 107.6098],
        ['33', 'Jawa Tengah', -7.1510, 110.1403],
        ['34', 'DI Yogyakarta', -7.7956, 110.3695],
        ['35', 'Jawa Timur', -7.5361, 112.2384],
        ['36', 'Banten', -6.4058, 106.0640],
        ['51', 'Bali', -8.4095, 115.1889],
        ['52', 'Nusa Tenggara Barat', -8.6529, 117.3616],
        ['53', 'Nusa Tenggara Timur', -8.6574, 121.0794],
        ['61', 'Kalimantan Barat', -0.2788, 111.4753],
        ['62', 'Kalimantan Tengah', -1.6815, 113.3824],
        ['63', 'Kalimantan Selatan', -3.0926, 115.2838],
        ['64', 'Kalimantan Timur', 0.5387, 116.4194],
        ['65', 'Kalimantan Utara', 3.0731, 116.0414],
        ['71', 'Sulawesi Utara', 0.6247, 123.9750],
        ['72', 'Sulawesi Tengah', -1.4300, 121.4456],
        ['73', 'Sulawesi Selatan', -3.6688, 119.9741],
        ['74', 'Sulawesi Tenggara', -4.1449, 122.1746],
        ['75', 'Gorontalo', 0.6999, 122.4467],
        ['76', 'Sulawesi Barat', -2.8441, 119.2321],
        ['81', 'Maluku', -3.2385, 130.1453],
        ['82', 'Maluku Utara', 1.5709, 127.8088],
        ['91', 'Papua Barat', -1.3361, 133.1747],
        ['92', 'Papua Barat Daya', -0.8615, 131.2551],
        ['94', 'Papua', -2.5916, 140.6690],
        ['95', 'Papua Selatan', -7.1900, 139.6000],
        ['96', 'Papua Tengah', -3.9800, 136.0800],
        ['97', 'Papua Pegunungan', -4.0800, 138.9500],
    ];

    /**
     * Contoh hierarki untuk demo dan uji.
     *
     * Tiga daerah dari tiga pulau berbeda, sengaja dipilih supaya
     * tampilan tidak pernah terlihat seolah Resikita hanya berjalan di
     * satu provinsi.
     *
     * ## Kenapa tiap simpul membawa koordinatnya sendiri
     *
     * Sebelumnya seluruh kabupaten, kecamatan, dan desa mewarisi titik
     * pusat provinsinya. Akibatnya seluruh desa se-Bali berhimpit di satu
     * koordinat, dan `WilayahResolverService`, yang mencari wilayah
     * terdekat dalam radius 25 kilometer, tidak punya apa pun untuk
     * dibedakan. Laporan dari Kuta Selatan, yang jaraknya lebih dari 40
     * kilometer dari titik pusat Bali, malah tidak menemukan wilayah mana
     * pun dan jatuh ke fasilitator seolah Badung belum bergabung.
     *
     * Titik di bawah adalah perkiraan pusat wilayah sungguhan, cukup
     * teliti untuk resolusi radius dan peta demo. Data resmi yang presisi
     * tetap masuk lewat `php artisan wilayah:impor`.
     *
     * Bentuk: kode provinsi => [kode kab, nama, lat, lng, [kecamatan...]]
     * dan tiap kecamatan membawa [kode, nama, lat, lng, [desa...]].
     *
     * @var array<string, array<int, array{0: string, 1: string, 2: float, 3: float, 4: array<int, array{0: string, 1: string, 2: float, 3: float, 4: array<int, array{0: string, 1: string, 2: float, 3: float}>}>}>>
     */
    private const CONTOH = [
        '51' => [
            ['03', 'Badung', -8.5800, 115.1800, [
                ['01', 'Kuta Selatan', -8.8000, 115.1700, [
                    ['2001', 'Jimbaran', -8.7900, 115.1650],
                    ['2002', 'Benoa', -8.7700, 115.2000],
                    ['2003', 'Ungasan', -8.8300, 115.1600],
                ]],
                ['02', 'Kuta', -8.7200, 115.1750, [
                    ['2001', 'Kuta', -8.7180, 115.1690],
                    ['2002', 'Legian', -8.7050, 115.1700],
                    ['2003', 'Seminyak', -8.6900, 115.1650],
                ]],
                ['05', 'Mengwi', -8.5600, 115.1750, [
                    ['2001', 'Mengwi', -8.5540, 115.1690],
                    ['2002', 'Sempidi', -8.5900, 115.1850],
                    ['2003', 'Lukluk', -8.5980, 115.1900],
                ]],
            ]],
            ['71', 'Denpasar', -8.6500, 115.2160, [
                ['01', 'Denpasar Selatan', -8.7000, 115.2250, [
                    ['1001', 'Sesetan', -8.7050, 115.2200],
                    ['1002', 'Sanur', -8.6900, 115.2600],
                    ['1003', 'Renon', -8.6700, 115.2350],
                ]],
                ['02', 'Denpasar Timur', -8.6500, 115.2450, [
                    ['1001', 'Kesiman', -8.6500, 115.2500],
                    ['1002', 'Sumerta', -8.6550, 115.2300],
                    ['1003', 'Dangin Puri', -8.6600, 115.2200],
                ]],
            ]],
        ],

        '34' => [
            ['04', 'Sleman', -7.7160, 110.3550, [
                ['09', 'Depok', -7.7650, 110.3950, [
                    ['1001', 'Caturtunggal', -7.7700, 110.3900],
                    ['1002', 'Maguwoharjo', -7.7650, 110.4200],
                    ['1003', 'Condongcatur', -7.7500, 110.4000],
                ]],
                ['12', 'Ngaglik', -7.7100, 110.4000, [
                    ['2001', 'Sardonoharjo', -7.7000, 110.3900],
                    ['2002', 'Sinduharjo', -7.7250, 110.4000],
                    ['2003', 'Sukoharjo', -7.6950, 110.4100],
                ]],
            ]],
            ['02', 'Bantul', -7.8880, 110.3290, [
                ['07', 'Kasihan', -7.8300, 110.3250, [
                    ['2001', 'Bangunjiwo', -7.8500, 110.3100],
                    ['2002', 'Tirtonirmolo', -7.8300, 110.3400],
                    ['2003', 'Tamantirto', -7.8200, 110.3300],
                ]],
            ]],
        ],

        '53' => [
            ['71', 'Kupang', -10.1772, 123.6070, [
                ['01', 'Alak', -10.1600, 123.5500, [
                    ['1001', 'Alak', -10.1600, 123.5500],
                    ['1002', 'Naioni', -10.1400, 123.5300],
                    ['1003', 'Manulai II', -10.1700, 123.5600],
                ]],
                ['03', 'Kelapa Lima', -10.1500, 123.6250, [
                    ['1001', 'Kelapa Lima', -10.1550, 123.6150],
                    ['1002', 'Oesapa', -10.1450, 123.6400],
                    ['1003', 'Lasiana', -10.1400, 123.6600],
                ]],
            ]],
            ['10', 'Sikka', -8.6200, 122.2100, [
                ['06', 'Alok', -8.6200, 122.2100, [
                    ['1001', 'Kota Uneng', -8.6200, 122.2050],
                    ['1002', 'Kota Baru', -8.6250, 122.2150],
                    ['1003', 'Wailiti', -8.6150, 122.1950],
                ]],
            ]],
        ],
    ];

    public function run(): void
    {
        $provinsi = $this->semaiProvinsi();

        foreach (self::CONTOH as $kodeProvinsi => $daftarKabupaten) {
            $induk = $provinsi[$kodeProvinsi] ?? null;

            if ($induk === null) {
                continue;
            }

            foreach ($daftarKabupaten as [$kodeKab, $namaKab, $latKab, $lngKab, $daftarKecamatan]) {
                $kabupaten = $this->semai(
                    $induk->kode.'.'.$kodeKab,
                    $namaKab,
                    TingkatWilayah::Kabupaten,
                    $induk,
                    $latKab,
                    $lngKab,
                );

                foreach ($daftarKecamatan as [$kodeKec, $namaKec, $latKec, $lngKec, $daftarDesa]) {
                    $kecamatan = $this->semai(
                        $kabupaten->kode.'.'.$kodeKec,
                        $namaKec,
                        TingkatWilayah::Kecamatan,
                        $kabupaten,
                        $latKec,
                        $lngKec,
                    );

                    foreach ($daftarDesa as [$kodeDesa, $namaDesa, $latDesa, $lngDesa]) {
                        $this->semai(
                            $kecamatan->kode.'.'.$kodeDesa,
                            $namaDesa,
                            TingkatWilayah::Desa,
                            $kecamatan,
                            $latDesa,
                            $lngDesa,
                        );
                    }
                }
            }
        }
    }

    /** @return array<string, Wilayah> */
    private function semaiProvinsi(): array
    {
        $hasil = [];

        foreach (self::PROVINSI as [$kode, $nama, $lat, $lng]) {
            $hasil[$kode] = Wilayah::updateOrCreate(
                ['kode' => $kode],
                [
                    'nama' => $nama,
                    'tingkat' => TingkatWilayah::Provinsi,
                    'parent_id' => null,
                    'latitude' => $lat,
                    'longitude' => $lng,
                ],
            );
        }

        return $hasil;
    }

    /**
     * Simpan satu simpul.
     *
     * Koordinat diambil dari daftar bila ada, dan hanya jatuh ke titik
     * induknya sebagai cadangan terakhir. Warisan itu dulu dipakai untuk
     * semua simpul, dan itulah yang membuat seluruh desa berhimpit di
     * satu titik sehingga resolusi wilayah kehilangan pembandingnya.
     * Titik yang presisi tetap masuk lewat perintah impor data resmi.
     */
    private function semai(
        string $kode,
        string $nama,
        TingkatWilayah $tingkat,
        Wilayah $induk,
        ?float $latitude = null,
        ?float $longitude = null,
    ): Wilayah {
        return Wilayah::updateOrCreate(
            ['kode' => $kode],
            [
                'nama' => $nama,
                'tingkat' => $tingkat,
                'parent_id' => $induk->id,
                'latitude' => $latitude ?? $induk->latitude,
                'longitude' => $longitude ?? $induk->longitude,
            ],
        );
    }
}
