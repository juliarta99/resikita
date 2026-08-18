<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AlasanRouting;
use App\Enums\JenisTps;
use App\Enums\KategoriSampah;
use App\Enums\Role as RoleEnum;
use App\Enums\StatusAktif;
use App\Enums\StatusArtikel;
use App\Enums\StatusPengajuanWilayah;
use App\Enums\StatusProgres;
use App\Enums\StatusRegistrasiWilayah;
use App\Enums\StatusUmkm;
use App\Enums\SumberInput;
use App\Enums\TipeArtikel;
use App\Models\Artikel;
use App\Models\ArtikelKategori;
use App\Models\BankSampah;
use App\Models\BankSampahHarga;
use App\Models\Dompet;
use App\Models\Laporan;
use App\Models\LaporanKategori;
use App\Models\PengajuanWilayah;
use App\Models\Produk;
use App\Models\ProdukKategori;
use App\Models\Tps;
use App\Models\Umkm;
use App\Models\UmkmDompet;
use App\Models\User;
use App\Models\Wilayah;
use App\Services\Auth\AkunService;
use App\Services\Konten\TeksBacaService;
use App\Services\Laporan\LaporanService;
use App\Services\Laporan\TindakLanjutService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Data demo untuk menjalankan seluruh alur tanpa mengetik apa pun.
 *
 * Sengaja tersebar di tiga provinsi dari tiga pulau berbeda. Demo yang
 * seluruh datanya berasal dari satu kabupaten akan terlihat persis
 * seperti yang ingin dihindari produk ini: sistem daerah yang mengaku
 * nasional.
 *
 * Seeder ini tidak dijalankan di produksi. Kata sandi seragam dan lemah
 * memang disengaja untuk demo, dan justru karena itu ia harus tetap
 * jauh dari basis data sungguhan.
 *
 * ## Yang disemai di sini dan yang tidak
 *
 * Berkas ini menyemai **kerangkanya**: wilayah, akun, bank sampah, TPS,
 * UMKM, artikel, dan laporan beserta daur hidupnya. Transaksi yang
 * memindahkan uang dan stok ada di DemoTransaksiSeeder, dan jejak
 * pemakaian fitur di DemoInteraksiSeeder. Pemisahan itu bukan kerapian
 * semata: keduanya bergantung pada saldo dan produk yang baru ada
 * setelah berkas ini selesai.
 *
 * Daftar akun lengkapnya ada di AKUN-DEMO.md di akar repositori.
 */
class DemoSeeder extends Seeder
{
    public const KATA_SANDI = 'password';

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('DemoSeeder tidak boleh dijalankan di produksi.');

            return;
        }

        $badung = $this->wilayah('51.03');
        $sleman = $this->wilayah('34.04');
        $sikka = $this->wilayah('53.10');

        if ($badung === null || $sleman === null || $sikka === null) {
            $this->command?->error('WilayahSeeder harus dijalankan lebih dulu.');

            return;
        }

        // Dua wilayah bergabung, satu sengaja dibiarkan belum, supaya
        // papan Fasilitator Wilayah punya isi sejak awal dan alur
        // pengajuan wilayah bisa diperagakan tanpa menyiapkan apa pun.
        $this->verifikasi($badung);
        $this->verifikasi($sleman);

        $this->semaiAkunPlatform();
        $this->semaiPemerintah($badung, $sleman);
        $this->semaiWarga($badung, $sleman, $sikka);
        $this->semaiBankSampah($badung, $sleman);
        $this->semaiTps($badung, $sleman);
        $this->semaiUmkm($badung, $sleman, $sikka);
        $this->semaiArtikel();
        $this->semaiLaporan($badung, $sikka);
        $this->semaiPengajuanWilayah($sikka);

        $this->command?->newLine();
        $this->command?->info('Kerangka demo siap. Kata sandi seluruh akun: '.self::KATA_SANDI);
    }

    // ----------------------------------------------------------------
    // Akun
    // ----------------------------------------------------------------

    private function semaiAkunPlatform(): void
    {
        $this->akun('admin@resikita.id', 'Rizky Ananda Putra', RoleEnum::Admin, [
            'phone' => '081211110001',
        ]);

        $this->akun('fasilitator@resikita.id', 'Dewi Anggraeni', RoleEnum::FasilitatorWilayah, [
            'phone' => '081211110002',
        ]);
    }

    private function semaiPemerintah(Wilayah $badung, Wilayah $sleman): void
    {
        $this->akun('provinsi.bali@resikita.id', 'I Gusti Ngurah Rai Wijaya', RoleEnum::AdminProvinsi, [
            'wilayah_id' => $badung->parent_id,
            'phone' => '081233330001',
        ]);

        $this->akun('provinsi.diy@resikita.id', 'Raden Bagus Nugroho', RoleEnum::AdminProvinsi, [
            'wilayah_id' => $sleman->parent_id,
            'phone' => '081233330002',
        ]);

        $this->akun('kabupaten.badung@resikita.id', 'I Made Sudiarta', RoleEnum::AdminKabupaten, [
            'wilayah_id' => $badung->id,
            'phone' => '081233330003',
        ]);

        $this->akun('kabupaten.sleman@resikita.id', 'Sri Wahyuni', RoleEnum::AdminKabupaten, [
            'wilayah_id' => $sleman->id,
            'phone' => '081233330004',
        ]);

        // Kepala desa dipilih dari desa yang benar-benar ada di hierarki,
        // bukan desa pertama yang kebetulan terambil. Nama desanya ikut
        // masuk ke nama akun supaya cakupannya terbaca di layar.
        $mengwi = $this->wilayah('51.03.05.2001');
        $sardonoharjo = $this->wilayah('34.04.12.2001');

        if ($mengwi !== null) {
            $this->akun('desa.mengwi@resikita.id', 'I Nyoman Astawa', RoleEnum::KepalaDesa, [
                'wilayah_id' => $mengwi->id,
                'phone' => '081233330005',
            ]);
        }

        if ($sardonoharjo !== null) {
            $this->akun('desa.sardonoharjo@resikita.id', 'Bambang Sutrisno', RoleEnum::KepalaDesa, [
                'wilayah_id' => $sardonoharjo->id,
                'phone' => '081233330006',
            ]);
        }

        $petugas = [
            ['petugas.badung1@resikita.id', 'I Wayan Sukadana', $badung, '081244440001'],
            ['petugas.badung2@resikita.id', 'I Ketut Merta', $badung, '081244440002'],
            ['petugas.sleman1@resikita.id', 'Agus Purnomo', $sleman, '081244440003'],
        ];

        foreach ($petugas as [$email, $nama, $wilayah, $phone]) {
            $this->akun($email, $nama, RoleEnum::Petugas, [
                'wilayah_id' => $wilayah->id,
                'phone' => $phone,
            ]);
        }
    }

    private function semaiWarga(Wilayah $badung, Wilayah $sleman, Wilayah $sikka): void
    {
        $daftar = [
            ['warga.kadek@resikita.id', 'Ni Kadek Sari Dewi', $badung, '081255550001'],
            ['warga.wayan@resikita.id', 'I Wayan Gede Suparta', $badung, '081255550002'],
            ['warga.komang@resikita.id', 'Komang Ayu Lestari', $badung, '081255550003'],
            ['warga.siti@resikita.id', 'Siti Nurhaliza Rahmawati', $sleman, '081255550004'],
            ['warga.budi@resikita.id', 'Budi Santoso', $sleman, '081255550005'],

            // Pelapor dari wilayah yang belum bergabung. Laporannya yang
            // membuat papan fasilitator punya isi.
            ['warga.maria@resikita.id', 'Maria Yosefina Da Costa', $sikka, '081255550006'],
        ];

        foreach ($daftar as [$email, $nama, $wilayah, $phone]) {
            $this->akun($email, $nama, RoleEnum::Masyarakat, [
                'wilayah_id' => $wilayah->id,
                'phone' => $phone,
            ]);
        }
    }

    // ----------------------------------------------------------------
    // Entitas
    // ----------------------------------------------------------------

    private function semaiBankSampah(Wilayah $badung, Wilayah $sleman): void
    {
        /*
         * Dua unit dengan katalog harga berbeda untuk jenis yang sama.
         * Itu bukan variasi hiasan: harga sampah memang per unit sejak
         * skema ini, dan demo yang harganya seragam di semua unit
         * menyembunyikan justru perubahan yang paling penting.
         *
         * Nilai rupiah penuh sebagai integer, bukan rupiah dikali seratus.
         */
        $unit = [
            [
                'Bank Sampah Sari Lestari',
                'Jalan Raya Mengwi Nomor 27, Mengwi',
                -8.5540, 115.1690, '081266660001', $badung,
                'banksampah.badung@resikita.id', 'Ni Luh Putu Ariani',
                [
                    ['Botol PET bening', KategoriSampah::Anorganik, 3_500],
                    ['Kardus', KategoriSampah::Anorganik, 2_000],
                    ['Kertas HVS', KategoriSampah::Anorganik, 2_500],
                    ['Koran bekas', KategoriSampah::Anorganik, 1_800],
                    ['Kaleng aluminium', KategoriSampah::Anorganik, 12_000],
                    ['Plastik HDPE', KategoriSampah::Anorganik, 4_000],
                    ['Beling', KategoriSampah::Anorganik, 500],
                    ['Minyak jelantah', KategoriSampah::Residu, 6_000],
                    ['Elektronik kecil', KategoriSampah::Elektronik, 8_000],
                    ['Baterai bekas', KategoriSampah::B3, 1_000],
                ],
            ],
            [
                'Bank Sampah Guyub Rukun',
                'Jalan Kaliurang Kilometer 10, Ngaglik',
                -7.7100, 110.4000, '081266660002', $sleman,
                'banksampah.sleman@resikita.id', 'Retno Palupi',
                [
                    ['Botol PET bening', KategoriSampah::Anorganik, 3_200],
                    ['Kardus', KategoriSampah::Anorganik, 2_200],
                    ['Kertas HVS', KategoriSampah::Anorganik, 2_400],
                    ['Kaleng aluminium', KategoriSampah::Anorganik, 11_500],
                    ['Plastik HDPE', KategoriSampah::Anorganik, 3_800],
                    ['Minyak jelantah', KategoriSampah::Residu, 6_500],
                    ['Elektronik kecil', KategoriSampah::Elektronik, 7_500],
                ],
            ],
        ];

        foreach ($unit as [$nama, $alamat, $lat, $lng, $phone, $wilayah, $email, $pengelola, $katalog]) {
            $bank = BankSampah::updateOrCreate(
                ['nama' => $nama],
                [
                    'alamat' => $alamat,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'phone' => $phone,
                    'wilayah_id' => $wilayah->id,
                    'status' => StatusAktif::Aktif,
                    'is_verified' => true,
                ],
            );

            $this->akun($email, $pengelola, RoleEnum::BankSampah, [
                'bank_sampah_id' => $bank->id,
                'wilayah_id' => $wilayah->id,
                'phone' => $phone,
            ]);

            foreach ($katalog as [$jenis, $kategori, $harga]) {
                BankSampahHarga::updateOrCreate(
                    ['bank_sampah_id' => $bank->id, 'jenis_sampah' => $jenis],
                    [
                        'kategori' => $kategori,
                        'satuan' => 'kg',
                        'harga_per_satuan' => $harga,
                        'is_active' => true,
                    ],
                );
            }
        }
    }

    private function semaiTps(Wilayah $badung, Wilayah $sleman): void
    {
        $daftar = [
            ['TPS3R Mengwi Bersih', 'Jalan Raya Mengwitani, Mengwi', $badung, JenisTps::Tps3r, true, 25_000, -8.5545, 115.1695, 18.5],
            ['TPS Kuta Selatan', 'Jalan Uluwatu Nomor 88, Jimbaran', $badung, JenisTps::Tps, false, null, -8.7900, 115.1780, 8.0],
            ['TPS3R Ngaglik Asri', 'Jalan Palagan Tentara Pelajar, Ngaglik', $sleman, JenisTps::Tps3r, true, 20_000, -7.7105, 110.4005, 12.0],
            ['TPS Depok Sejahtera', 'Jalan Affandi, Caturtunggal', $sleman, JenisTps::Tps, false, null, -7.7700, 110.3900, 6.5],
        ];

        foreach ($daftar as [$nama, $alamat, $wilayah, $jenis, $berbayar, $tarif, $lat, $lng, $kapasitas]) {
            Tps::updateOrCreate(
                ['nama' => $nama],
                [
                    'alamat' => $alamat,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'jenis' => $jenis,
                    'is_berbayar' => $berbayar,
                    'tarif_bulanan' => $tarif,
                    'kapasitas_ton' => $kapasitas,
                    'wilayah_id' => $wilayah->id,
                ],
            );
        }
    }

    /**
     * Empat UMKM dalam empat keadaan verifikasi yang berbeda.
     *
     * Dua aktif supaya marketplace punya isi dari lebih dari satu toko,
     * tanpa itu, pemecahan pesanan per toko dan ongkir yang berbeda antar
     * penjual tidak bisa diperagakan sama sekali. Satu menunggu dan satu
     * ditolak supaya antrean verifikasi admin dan halaman status
     * pendaftaran punya isi sejak awal.
     */
    private function semaiUmkm(Wilayah $badung, Wilayah $sleman, Wilayah $sikka): void
    {
        $kategori = ProdukKategori::query()->orderBy('id')->pluck('id', 'slug');

        if ($kategori->isEmpty()) {
            $this->command?->warn('Kategori produk kosong, produk UMKM dilewati.');
        }

        // ---- 1. Aktif, Sleman ----------------------------------------
        $rumahDaur = $this->umkm(
            'Rumah Daur Sleman',
            [
                'deskripsi' => 'Mengolah kemasan sachet dan kain perca menjadi tas, dompet, dan perabot rumah. Berdiri sejak 2019 bersama sepuluh ibu rumah tangga di Ngaglik.',
                'alamat' => 'Jalan Kaliurang Kilometer 9, Sardonoharjo, Ngaglik',
                'latitude' => -7.7000,
                'longitude' => 110.3900,
                'phone' => '081277770001',
                'email' => 'halo@rumahdaursleman.id',
                'wilayah_id' => $sleman->id,

                /*
                 * Id wilayah penyedia ongkir. Tanpa ini produk tidak bisa
                 * masuk keranjang sama sekali, toko tanpa titik asal
                 * tidak bisa dihitung ongkirnya.
                 *
                 * PERIKSA SEBELUM DEMO: id ini milik RajaOngkir dan bisa
                 * berubah. Cara memastikannya ada di AKUN-DEMO.md.
                 */
                'destination_id' => 16_374,
                'alamat_asal' => 'SARDONOHARJO, NGAGLIK, SLEMAN, DI YOGYAKARTA, 55581',

                'status' => StatusUmkm::Aktif,
                'is_verified' => true,
            ],
        );

        $this->akun('umkm.sleman@resikita.id', 'Endang Kusumawati', RoleEnum::Umkm, [
            'umkm_id' => $rumahDaur->id,
            'wilayah_id' => $sleman->id,
            'phone' => '081277770001',
        ]);

        $this->semaiProduk($rumahDaur, $kategori, [
            ['Tas Anyam Sachet Motif Parang', 'tas-dan-dompet', 'Kemasan sachet bekas', 85_000, 12, 450],
            ['Dompet Kain Perca Batik', 'tas-dan-dompet', 'Kain perca konveksi', 45_000, 24, 180],
            ['Pot Gantung Botol PET', 'pot-dan-berkebun', 'Botol PET bekas', 25_000, 40, 220],
            ['Keranjang Koran Gulung', 'kerajinan', 'Koran bekas', 65_000, 8, 600],
            ['Tikar Lipat Sachet', 'perabot-rumah', 'Kemasan sachet bekas', 120_000, 6, 900],
        ]);

        // ---- 2. Aktif, Badung ----------------------------------------
        $kriyaMengwi = $this->umkm(
            'Kriya Sampah Mengwi',
            [
                'deskripsi' => 'Kelompok perajin Mengwi yang mengubah botol kaca, ban dalam bekas, dan serbuk kayu menjadi dekorasi dan perabot.',
                'alamat' => 'Banjar Pandean, Jalan Raya Mengwi Nomor 45, Mengwi',
                'latitude' => -8.5530,
                'longitude' => 115.1680,
                'phone' => '081277770002',
                'email' => 'kriya@sampahmengwi.id',
                'wilayah_id' => $badung->id,

                // PERIKSA SEBELUM DEMO, lihat catatan pada toko di atas.
                'destination_id' => 17_473,
                'alamat_asal' => 'KEROBOKAN KELOD, KUTA UTARA, BADUNG, BALI, 80361',

                'status' => StatusUmkm::Aktif,
                'is_verified' => true,
            ],
        );

        $this->akun('umkm.badung@resikita.id', 'I Putu Gede Ardana', RoleEnum::Umkm, [
            'umkm_id' => $kriyaMengwi->id,
            'wilayah_id' => $badung->id,
            'phone' => '081277770002',
        ]);

        $this->semaiProduk($kriyaMengwi, $kategori, [
            ['Lampu Meja Botol Kaca', 'dekorasi', 'Botol kaca bekas', 145_000, 10, 1_200],
            ['Pot Tanaman Ban Bekas', 'pot-dan-berkebun', 'Ban dalam bekas', 55_000, 18, 1_500],
            ['Jam Dinding Serbuk Kayu', 'dekorasi', 'Serbuk gergaji', 95_000, 7, 700],
            ['Kompos Padat 5 Kilogram', 'kompos-dan-pupuk', 'Sampah organik pasar', 30_000, 50, 5_000],
        ]);

        // ---- 3. Menunggu verifikasi ----------------------------------
        // Sengaja tanpa produk: toko yang belum lolos memang belum bisa
        // mengunggah apa pun, dan panel penjualnya masih tertutup.
        $anyamanSikka = $this->umkm(
            'Anyaman Bambu Sikka',
            [
                'deskripsi' => 'Kelompok ibu-ibu Kota Uneng yang menganyam bambu dan daun lontar menjadi wadah pengganti kantong plastik.',
                'alamat' => 'Jalan Nairoa, Kota Uneng, Alok',
                'latitude' => -8.6200,
                'longitude' => 122.2100,
                'phone' => '081277770003',
                'email' => 'anyaman@bambusikka.id',
                'wilayah_id' => $sikka->id,

                'status' => StatusUmkm::Menunggu,
                'is_verified' => false,
            ],
        );

        $this->akun('umkm.sikka@resikita.id', 'Maria Fatima Wangge', RoleEnum::Umkm, [
            'umkm_id' => $anyamanSikka->id,
            'wilayah_id' => $sikka->id,
            'phone' => '081277770003',
        ]);

        // ---- 4. Ditolak, menunggu perbaikan --------------------------
        $kupang = $this->umkm(
            'Daur Ulang Kupang Mandiri',
            [
                'deskripsi' => 'Usaha rumahan pengolahan sampah plastik.',
                'alamat' => 'Kupang',
                'phone' => '081277770004',
                'email' => 'kupangmandiri@contoh.id',
                'wilayah_id' => $this->wilayah('53.71')?->id,
            ],
        );

        $pemilikKupang = $this->akun('umkm.kupang@resikita.id', 'Yohanes Baptista Lado', RoleEnum::Umkm, [
            'umkm_id' => $kupang->id,
            'phone' => '081277770004',
        ]);

        // Penolakan lewat Service, bukan dengan menyetel kolom langsung.
        // Dengan begitu catatan, jejak peninjau, dan baris notifikasinya
        // benar-benar terbentuk seperti pada penolakan sungguhan.
        if (! $kupang->ditolak()) {
            $admin = User::query()->where('email', 'admin@resikita.id')->first();

            if ($admin !== null) {
                app(AkunService::class)->tolakUmkm(
                    $kupang,
                    $admin,
                    'Alamat usaha baru terisi "Kupang" saja, sehingga lokasinya tidak bisa dipastikan. '
                    .'Tuliskan sampai nama jalan dan nomor, lalu lengkapi deskripsi bahan baku yang diolah.',
                );
            }
        }

        unset($pemilikKupang);
    }

    private function semaiArtikel(): void
    {
        $kategori = ArtikelKategori::query()->pluck('id', 'slug');
        $penulis = User::query()->where('email', 'admin@resikita.id')->value('id');
        $teksBaca = app(TeksBacaService::class);

        $daftar = [
            [
                'Memilah Sampah Rumah Tangga Mulai dari Dapur',
                'pemilahan',
                TipeArtikel::Panduan,
                "Pemilahan yang berhasil hampir selalu dimulai dari satu titik: dapur.\n\n".
                "Sediakan dua wadah berdampingan. Satu untuk sisa makanan dan kulit buah, satu lagi untuk kemasan kering yang sudah dibilas. Dua wadah sudah cukup untuk memulai; menambah wadah ketiga sebelum kebiasaan terbentuk justru membuat orang menyerah.\n\n".
                "Bilas kemasan sebelum dikumpulkan. Sisa kuah atau minyak membuat kertas dan plastik kehilangan nilai jualnya di bank sampah, dan sering membuat seluruh kantong ditolak.\n\n".
                'Setelah dua pekan, tambahkan wadah khusus untuk baterai, lampu, dan obat kedaluwarsa. Simpan tertutup dan jangan pernah dicampur dengan sampah harian.',
            ],
            [
                'Mengompos di Rumah Tanpa Halaman Luas',
                'kompos',
                TipeArtikel::Tutorial,
                "Kompos tidak menuntut kebun. Ember bekas cat berukuran dua puluh liter sudah cukup untuk satu rumah tangga kecil.\n\n".
                "Lubangi bagian bawah dan sisi ember untuk sirkulasi udara. Isi bergantian: satu lapis sisa dapur, satu lapis bahan kering seperti daun atau serbuk gergaji.\n\n".
                "Aduk seminggu sekali. Bau asam berarti terlalu basah, tambahkan bahan kering. Kompos matang dalam enam sampai delapan pekan, berwarna gelap dan berbau tanah.\n\n".
                'Yang tidak boleh masuk: daging, tulang, minyak, dan kotoran hewan peliharaan.',
            ],
            [
                'Apa yang Terjadi pada Sampah Anda Setelah Disetor ke Bank Sampah',
                'bank-sampah',
                TipeArtikel::Artikel,
                "Banyak orang berhenti bertanya setelah menerima saldo. Padahal perjalanan sampahnya baru dimulai.\n\n".
                "Di bank sampah, setoran ditimbang per jenis dan dicatat dengan harga yang berlaku hari itu. Harga berbeda antar unit dan berubah mengikuti pasar pengepul.\n\n".
                "Anorganik bernilai kemudian dikirim ke pengepul lalu ke pabrik daur ulang. Botol PET menjadi serat poliester, kardus menjadi kertas daur ulang, aluminium dilebur berulang kali nyaris tanpa kehilangan mutu.\n\n".
                'Yang tidak terserap aliran itu berakhir di TPA. Karena itu memilah lebih dalam bukan sekadar kerapian, ia menentukan berapa banyak yang benar-benar selamat.',
            ],
            [
                'Limbah B3 Rumah Tangga: Yang Kecil tapi Paling Berbahaya',
                'limbah-b3',
                TipeArtikel::Panduan,
                "Baterai, lampu neon, sisa cat, obat kedaluwarsa, dan kemasan pestisida masuk kategori bahan berbahaya dan beracun.\n\n".
                "Jumlahnya kecil, tapi satu baterai bocor mencemari tanah dalam radius yang jauh lebih luas daripada ukurannya.\n\n".
                "Simpan terpisah dalam wadah tertutup dan berlabel. Jangan pernah dibakar, jangan dicampur sampah harian, dan jangan dibuang ke saluran air.\n\n".
                'Serahkan ke fasilitas penampungan limbah B3 atau tanyakan titik pengumpulan ke dinas lingkungan hidup setempat.',
            ],
            [
                'Membaca Aturan Sampah: Undang-Undang 18 Tahun 2008 dalam Bahasa Sehari-hari',
                'regulasi',
                TipeArtikel::Artikel,
                "Undang-Undang Nomor 18 Tahun 2008 menempatkan pengelolaan sampah sebagai urusan wajib pemerintah kabupaten dan kota. Itu sebabnya laporan warga di Resikita diteruskan lebih dulu ke tingkat kabupaten.\n\n".
                "Undang-undang yang sama memperkenalkan asas tanggung jawab produsen: yang membuat kemasan ikut bertanggung jawab atas nasib kemasannya.\n\n".
                "Peraturan Pemerintah Nomor 81 Tahun 2012 kemudian mengatur pengurangan dan penanganan sampah rumah tangga, sementara Peraturan Presiden Nomor 97 Tahun 2017 menetapkan sasaran nasionalnya.\n\n".
                'Yang perlu diingat warga cukup satu hal: memilah bukan kebaikan sukarela semata, melainkan bagian dari kewajiban yang sudah punya dasar hukum.',
            ],
            [
                'Ekonomi Sirkular Bukan Sekadar Daur Ulang',
                'ekonomi-sirkular',
                TipeArtikel::Artikel,
                "Daur ulang sering dianggap puncak dari pengelolaan sampah. Padahal dalam ekonomi sirkular ia justru langkah terakhir sebelum menyerah.\n\n".
                "Urutannya: menolak yang tidak perlu, mengurangi yang dipakai, memakai ulang selama mungkin, baru mendaur ulang sisanya.\n\n".
                "Sebuah gelas yang dipakai ulang seratus kali menghemat jauh lebih banyak daripada seratus gelas sekali pakai yang semuanya berhasil didaur ulang.\n\n".
                'Karena itu bank sampah dan UMKM daur ulang tidak berdiri sendiri. Keduanya menahan bahan tetap berputar di dalam ekonomi, bukan sekadar memindahkannya ke tempat lain.',
            ],
            [
                'Memilih Bank Sampah yang Tepat di Sekitar Rumah',
                'bank-sampah',
                TipeArtikel::Panduan,
                "Tidak semua bank sampah menerima jenis yang sama, dan harganya pun berbeda antar unit.\n\n".
                "Sebelum menyetor, tanyakan tiga hal: jenis apa saja yang diterima, bagaimana cara penimbangan dicatat, dan kapan saldo bisa ditarik.\n\n".
                "Unit yang baik menimbang di depan nasabah dan mencatat per jenis, bukan menaksir keseluruhan kantong.\n\n".
                'Di Resikita, katalog harga tiap unit terbuka untuk dilihat sebelum Anda datang. Bandingkan lebih dulu, terutama untuk jenis yang Anda kumpulkan paling banyak.',
            ],
            [
                'Mengurangi Plastik Sekali Pakai Tanpa Merepotkan Diri Sendiri',
                'daur-ulang',
                TipeArtikel::Tutorial,
                "Perubahan yang bertahan hampir selalu yang paling sedikit menuntut.\n\n".
                "Mulai dari satu benda yang paling sering Anda pakai. Bagi kebanyakan orang itu kantong belanja, botol minum, atau wadah makan.\n\n".
                "Simpan penggantinya di tempat yang tidak bisa dilewatkan: tas kerja, jok motor, atau dekat kunci rumah. Niat baik kalah oleh lupa, dan lupa dikalahkan oleh penempatan.\n\n".
                'Setelah satu kebiasaan berjalan sebulan penuh, baru tambahkan yang kedua.',
            ],
        ];

        foreach ($daftar as $i => [$judul, $slugKategori, $tipe, $konten]) {
            $artikel = Artikel::firstOrNew(['slug' => Str::slug($judul)]);

            $artikel->fill([
                'penulis_id' => $penulis,
                'kategori_id' => $kategori[$slugKategori] ?? null,
                'tipe' => $tipe,
                'judul' => $judul,
                'konten' => $konten,
                'status' => StatusArtikel::Published,
                'is_unggulan' => $i < 3,
                'dilihat' => (8 - $i) * 37,
                'didengarkan' => (8 - $i) * 6,
                'published_at' => now()->subDays(40 - $i * 5),
            ]);

            // teks_baca selalu lewat Service, tidak pernah diketik
            // manual, web dan mobile harus membacakan kalimat yang sama.
            $teksBaca->siapkan($artikel);
            $artikel->save();
        }
    }

    /**
     * Laporan beserta daur hidupnya, bukan hanya baris berstatus baru.
     *
     * Panel pemerintah daerah baru terasa benar kalau setiap kolom
     * statusnya punya isi, dan waktu respons hanya bisa dihitung dari
     * laporan yang benar-benar pernah selesai.
     */
    private function semaiLaporan(Wilayah $badung, Wilayah $sikka): void
    {
        $kategori = LaporanKategori::query()->aktif()->orderBy('id')->get();

        $pelaporBadung = User::query()
            ->whereIn('email', [
                'warga.kadek@resikita.id',
                'warga.wayan@resikita.id',
                'warga.komang@resikita.id',
            ])
            ->get();

        $pelaporSikka = User::query()->where('email', 'warga.maria@resikita.id')->first();

        if ($kategori->isEmpty() || $pelaporBadung->isEmpty() || $pelaporSikka === null) {
            $this->command?->warn('Prasyarat laporan belum lengkap, penyemaian laporan dilewati.');

            return;
        }

        $service = app(LaporanService::class);
        $adminKab = User::query()->where('email', 'kabupaten.badung@resikita.id')->first();
        $petugas1 = User::query()->where('email', 'petugas.badung1@resikita.id')->first();
        $petugas2 = User::query()->where('email', 'petugas.badung2@resikita.id')->first();

        /*
         * Titik-titik di Badung sengaja dijauhkan lebih dari lima puluh
         * meter satu sama lain. Deteksi duplikat memakai radius itu, dan
         * demo yang seluruh laporannya saling dianggap duplikat akan
         * menyesatkan saat diperagakan.
         */
        $badungLaporan = [
            ['baru', -8.5545, 115.1695, 'Tumpukan sampah di bahu jalan Mengwi', 'Sudah menumpuk sekitar seminggu dan mulai berbau menyengat saat siang.', SumberInput::Ketik],
            ['baru', -8.7895, 115.1655, 'Sampah dibakar tiap sore di Jimbaran', 'Asapnya masuk ke rumah warga dan mengganggu anak-anak.', SumberInput::Suara],
            ['diverifikasi', -8.5905, 115.1855, 'Saluran air tersumbat sampah di Sempidi', 'Air tidak mengalir dan mulai menggenang setiap kali hujan turun.', SumberInput::Ketik],
            ['ditugaskan', -8.7185, 115.1695, 'TPS penuh belum terangkut di Kuta', 'Sampah meluber sampai ke bahu jalan sejak tiga hari lalu.', SumberInput::Ketik],
            ['dikerjakan', -8.6895, 115.1655, 'Sampah menumpuk di depan sekolah Seminyak', 'Menumpuk sejak libur panjang dan belum ada petugas yang datang.', SumberInput::Ketik],
            ['selesai', -8.5975, 115.1905, 'Bangkai kasur dibuang di lahan kosong Lukluk', 'Sudah dua pekan dibiarkan dan mulai menjadi sarang nyamuk.', SumberInput::Suara],
        ];

        foreach ($badungLaporan as $i => [$target, $lat, $lng, $judul, $deskripsi, $sumber]) {
            $laporan = $service->buat($pelaporBadung[$i % $pelaporBadung->count()], [
                'kategori_id' => $kategori[$i % $kategori->count()]->id,
                'judul' => $judul,
                'deskripsi' => $deskripsi,
                'latitude' => $lat,
                'longitude' => $lng,
                'deskripsi_sumber' => $sumber,
            ]);

            $this->majukanLaporan($laporan, $target, $service, $adminKab, $i % 2 === 0 ? $petugas1 : $petugas2);
        }

        // Wilayah belum terjangkau, masuk ke papan fasilitator dan
        // menaikkan skor prioritas perluasan wilayah.
        $sikkaLaporan = [
            [-8.6205, 122.2055, 'Sampah menumpuk di tepi pantai Kota Uneng', 'Sampah plastik terdampar sepanjang pantai dan belum ada yang membersihkan.', SumberInput::Suara],
            [-8.6255, 122.2155, 'Pembakaran sampah di dekat sekolah Kota Baru', 'Dilakukan hampir setiap pagi saat anak-anak berangkat sekolah.', SumberInput::Ketik],
            [-8.6155, 122.1955, 'Tidak ada TPS di Wailiti', 'Warga membuang ke kebun kosong karena tidak ada tempat resmi.', SumberInput::Ketik],
        ];

        foreach ($sikkaLaporan as $i => [$lat, $lng, $judul, $deskripsi, $sumber]) {
            $service->buat($pelaporSikka, [
                'kategori_id' => $kategori[($i + 2) % $kategori->count()]->id,
                'judul' => $judul,
                'deskripsi' => $deskripsi,
                'latitude' => $lat,
                'longitude' => $lng,
                'deskripsi_sumber' => $sumber,
            ]);
        }

        $this->semaiTindakLanjut();

        unset($sikka);
    }

    /** Jalankan satu laporan sampai status yang diinginkan. */
    private function majukanLaporan(
        Laporan $laporan,
        string $target,
        LaporanService $service,
        ?User $adminKab,
        ?User $petugas,
    ): void {
        if ($target === 'baru' || $adminKab === null) {
            return;
        }

        $service->verifikasi($laporan, $adminKab);
        $laporan->refresh();

        if ($target === 'diverifikasi' || $petugas === null) {
            return;
        }

        $service->tugaskan($laporan, $petugas, $adminKab, 'Mohon ditinjau dan ditangani sesuai prosedur.');
        $laporan->refresh();

        if ($target === 'ditugaskan') {
            return;
        }

        $service->catatProgres($laporan, $petugas, [
            'catatan' => 'Sudah tiba di lokasi dan mulai pengangkutan bersama dua orang rekan.',
            'status_progres' => StatusProgres::Dikerjakan,
            'latitude' => $laporan->latitude,
            'longitude' => $laporan->longitude,
        ]);
        $laporan->refresh();

        if ($target === 'dikerjakan') {
            return;
        }

        /*
         * Foto bukti wajib saat menyelesaikan, ditegakkan di Service.
         * Berkasnya sendiri tidak ikut disemai; yang tersimpan hanya
         * path-nya. Cara menaruh berkas contohnya ada di AKUN-DEMO.md.
         */
        $service->catatProgres($laporan, $petugas, [
            'catatan' => 'Lokasi sudah bersih. Warga sekitar diminta tidak membuang di titik ini lagi.',
            'foto_bukti' => 'laporan/bukti/contoh-bukti-selesai.jpg',
            'status_progres' => StatusProgres::Selesai,
            'latitude' => $laporan->latitude,
            'longitude' => $laporan->longitude,
        ]);
    }

    private function semaiTindakLanjut(): void
    {
        $fasilitator = User::query()->where('email', 'fasilitator@resikita.id')->first();

        if ($fasilitator === null) {
            return;
        }

        // Hanya laporan dari wilayah belum terjangkau yang boleh dicatat
        // tindak lanjutnya, ditegakkan TindakLanjutService.
        $laporan = Laporan::query()
            ->where('alasan_routing', AlasanRouting::WilayahBelumTerjangkau)
            ->orderBy('id')
            ->first();

        if ($laporan === null || $laporan->tindakLanjut()->exists()) {
            return;
        }

        app(TindakLanjutService::class)->catat($laporan, $fasilitator, [
            'nama_dinas' => 'Dinas Lingkungan Hidup Kabupaten Sikka',
            'kontak_dinas' => 'dlh@sikkakab.go.id',
            'tanggal_kontak' => now()->subDays(4)->toDateString(),
            'hasil' => 'Sudah dihubungi lewat surel dan telepon. Dinas menyatakan akan menjadwalkan '
                .'pembersihan pantai bersama kelompok masyarakat, dan menanyakan cara bergabung '
                .'dengan Resikita agar laporan warga masuk langsung ke mereka.',
        ]);
    }

    /**
     * Satu pengajuan wilayah yang masih menunggu tinjauan super admin.
     *
     * Tanpa ini, halaman verifikasi pengajuan wilayah tampil kosong dan
     * fitur yang paling menentukan cakupan nasional produk ini justru
     * tidak bisa diperagakan.
     */
    private function semaiPengajuanWilayah(Wilayah $sikka): void
    {
        PengajuanWilayah::updateOrCreate(
            ['wilayah_id' => $sikka->id, 'pemohon_email' => 'dlh@sikkakab.go.id'],
            [
                'pemohon_nama' => 'Yohanes Don Bosco Wangge',
                'pemohon_jabatan' => 'Kepala Dinas Lingkungan Hidup',
                'pemohon_phone' => '081288880001',
                'instansi' => 'Dinas Lingkungan Hidup Kabupaten Sikka',

                // Berkas suratnya tidak ikut disemai; lihat AKUN-DEMO.md
                // untuk cara menaruh contoh berkasnya di disk privat.
                'surat_path' => 'pengajuan-wilayah/contoh-surat-sikka.pdf',
                'status' => StatusPengajuanWilayah::Diajukan,
            ],
        );

        $sikka->update(['status_registrasi' => StatusRegistrasiWilayah::Diajukan]);
    }

    // ----------------------------------------------------------------
    // Pembantu
    // ----------------------------------------------------------------

    private function wilayah(string $kode): ?Wilayah
    {
        return Wilayah::query()->where('kode', $kode)->first();
    }

    private function verifikasi(Wilayah $wilayah): void
    {
        $wilayah->update([
            'status_registrasi' => StatusRegistrasiWilayah::Terverifikasi,
            'terverifikasi_at' => now(),
        ]);
    }

    /** @param array<string, mixed> $atribut */
    private function umkm(string $nama, array $atribut): Umkm
    {
        $umkm = Umkm::firstOrCreate(['nama' => $nama], $atribut);

        // Seluruh atribut dikembalikan ke keadaan yang dituju pada setiap
        // penjalanan, termasuk statusnya. Seeder demo memang harus bisa
        // mengembalikan basis data ke titik awal yang sama, toko yang
        // sengaja dibiarkan menunggu tidak boleh tetap aktif hanya karena
        // pernah disetujui saat peragaan sebelumnya.
        //
        // Toko yang statusnya ditetapkan lewat Service, yang ditolak,
        // sengaja tidak menyertakan `status` di sini.
        $umkm->fill($atribut)->save();

        UmkmDompet::firstOrCreate(['umkm_id' => $umkm->id], ['saldo' => 0]);

        return $umkm->fresh();
    }

    /**
     * @param  Collection<string, int>  $kategori
     * @param  array<int, array{0: string, 1: string, 2: string, 3: int, 4: int, 5: int}>  $daftar
     */
    private function semaiProduk(Umkm $umkm, $kategori, array $daftar): void
    {
        if ($kategori->isEmpty()) {
            return;
        }

        foreach ($daftar as [$nama, $slugKategori, $bahan, $harga, $stok, $berat]) {
            Produk::updateOrCreate(
                ['slug' => Str::slug($nama)],
                [
                    'umkm_id' => $umkm->id,
                    'kategori_id' => $kategori[$slugKategori] ?? $kategori->first(),
                    'nama' => $nama,
                    'deskripsi' => "Dibuat tangan dari {$bahan}. Setiap unit sedikit berbeda karena bahannya memang tidak seragam.",
                    'bahan_baku' => $bahan,
                    'harga' => $harga,
                    'stok' => $stok,
                    'berat_gram' => $berat,
                    'is_active' => true,
                ],
            );
        }
    }

    /** @param array<string, mixed> $atribut */
    private function akun(string $email, string $nama, RoleEnum $role, array $atribut = []): User
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $nama,
                'password' => Hash::make(self::KATA_SANDI),
                'kode_qr' => (string) Str::ulid(),
                'is_active' => true,
                'email_verified_at' => now(),
                ...$atribut,
            ],
        );

        // Atribut keterkaitan diperbarui juga pada akun yang sudah ada,
        // supaya menjalankan ulang seeder memperbaiki data yang telanjur
        // menyimpang alih-alih membiarkannya.
        if ($atribut !== []) {
            $user->fill($atribut)->save();
        }

        if (! $user->hasRole($role->value)) {
            $user->syncRoles([$role->value]);
        }

        Dompet::firstOrCreate(['user_id' => $user->id], ['saldo' => 0]);

        return $user->fresh();
    }
}
