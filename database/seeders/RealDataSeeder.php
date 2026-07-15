<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\BankSampah;
use App\Models\BanjarDinas;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use App\Models\Report;
use App\Models\ReportAssignment;
use App\Models\ReportCategory;
use App\Models\ReportProgress;
use App\Models\Tps;
use App\Models\TpsMember;
use App\Models\TpsSubscription;
use App\Models\Umkm;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WasteClassification;
use App\Models\WasteDeposit;
use App\Models\WasteDepositItem;
use App\Models\WastePrice;
use App\Models\Withdrawal;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/**
 * =====================================================================
 * SEEDER DATA RIIL — KABUPATEN BADUNG
 * =====================================================================
 *
 * KETERANGAN SUMBER DATA (penting dibaca sebelum dipakai demo/sidang):
 *
 * [RIIL]     Nama & fakta bersumber dari situs resmi Pemkab Badung dan
 *            pemberitaan media (per Juli 2026). Lihat komentar per blok.
 * [PERKIRAAN] Nama/koordinat masih perlu diverifikasi ke sumber resmi
 *            (DLHK Badung / peta desa). Ditandai jelas di tiap blok.
 * [FIKTIF]   Sengaja dibuat: warga, petugas, transaksi, UMKM.
 *            Data pribadi (NIK, HP, email, tanggal lahir) TIDAK riil —
 *            jangan pernah memakai data pribadi asli untuk demo.
 *
 * Catatan penting soal nama orang:
 * - Pejabat publik (Bupati, Wakil, Camat, Perbekel/Lurah) memakai nama
 *   riil karena jabatannya adalah informasi publik.
 * - Petugas bank sampah / petugas lapangan memakai nama FIKTIF, karena
 *   mereka individu privat — memakai nama asli orang untuk akun demo
 *   beserta transaksi karangan tidak etis dan berisiko.
 * - Email semua akun memakai domain demo (@demo.nitiresik.id) supaya
 *   tidak menyerupai kredensial instansi yang sebenarnya.
 *
 * Jalankan: php artisan db:seed --class=RealDataSeeder
 * =====================================================================
 */
class RealDataSeeder extends Seeder
{
    private $faker;
    private int $seq = 0;
    private array $saldo = [];

    /** Kata sandi seragam untuk semua akun demo. */
    private const PASSWORD = 'password';

    /** Domain email demo — sengaja bukan badungkab.go.id. */
    private const MAIL = '@demo.nitiresik.id';

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->faker = \Faker\Factory::create('id_ID');

        $this->command->info('› Wilayah Kabupaten Badung (6 kecamatan)...');
        $banjars = $this->seedWilayah();

        $this->command->info('› Pejabat: Bupati, Wakil, Dinas...');
        $this->seedEksekutif();

        $this->command->info('› Master: harga sampah & kategori...');
        $wastePrices = $this->seedMasterData();

        $this->command->info('› Warga & petugas lapangan...');
        [$masyarakat, $petugasLapangan] = $this->seedWarga($banjars);

        $this->command->info('› Bank sampah (berbasis banjar/desa)...');
        [$bankSampahs, $petugasBS] = $this->seedBankSampah();

        $this->command->info('› TPS3R Kabupaten Badung...');
        $this->seedTps($masyarakat);

        $this->command->info('› UMKM daur ulang & produk...');
        [$umkms, $products] = $this->seedUmkm();

        $this->command->info('› Setoran sampah...');
        $this->seedDeposits($bankSampahs, $petugasBS, $masyarakat, $wastePrices);

        $this->command->info('› Penarikan saldo...');
        $this->seedWithdrawals($masyarakat);

        $this->command->info('› Pesanan marketplace...');
        $this->seedOrders($masyarakat, $umkms, $products);

        $this->command->info('› Laporan sampah...');
        $this->seedReports($masyarakat, $petugasLapangan, $banjars);

        $this->command->info('› Klasifikasi AI, artikel, notifikasi...');
        $this->seedMisc($masyarakat);

        $this->command->info('✔ Seeder data riil Badung selesai.');
        $this->command->warn('  Login: lihat email di tabel users · sandi: ' . self::PASSWORD);
    }

    /* ================================================================
     |  HELPER
     * ================================================================ */

    private function makeUser(array $attrs, string $role, array $scope = []): User
    {
        $this->seq++;

        $user = User::create(array_merge([
            // NIK fiktif berpola 5103 (kode Kab. Badung) — bukan NIK asli.
            'nik'               => '5103' . str_pad((string) $this->seq, 12, '0', STR_PAD_LEFT),
            'tanggal_lahir'     => $this->faker->dateTimeBetween('-55 years', '-22 years')->format('Y-m-d'),
            'jenis_kelamin'     => 'L',
            'phone'             => '628' . str_pad((string) $this->seq, 9, '0', STR_PAD_LEFT),
            'phone_verified_at' => now(),
            'password'          => Hash::make(self::PASSWORD),
            'is_active'         => true,
        ], $attrs, $scope));

        $user->assignRole($role);

        return $user;
    }

    private function wallet(User $user, float $delta, string $tipe, $ref = null, ?string $ket = null): void
    {
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id], ['saldo' => 0]);
        $current = $this->saldo[$user->id] ?? (float) $wallet->saldo;
        $new = $current + $delta;

        if ($new < 0) {
            return;
        }

        $wallet->transactions()->create([
            'tipe'           => $tipe,
            'jumlah'         => $delta,
            'saldo_after'    => $new,
            'reference_type' => $ref?->getMorphClass(),
            'reference_id'   => $ref?->getKey(),
            'keterangan'     => $ket,
        ]);

        $wallet->update(['saldo' => $new]);
        $this->saldo[$user->id] = $new;
    }

    /** Titik acak kecil di sekitar koordinat pusat (agar marker tidak menumpuk). */
    private function near(float $lat, float $lng, float $spread = 0.004): array
    {
        return [
            'lat' => round($lat + $this->faker->randomFloat(5, -$spread, $spread), 7),
            'lng' => round($lng + $this->faker->randomFloat(5, -$spread, $spread), 7),
        ];
    }

    /* ================================================================
     |  WILAYAH — [RIIL] 6 kecamatan Kab. Badung beserta desa/kelurahan.
     |  Koordinat = [PERKIRAAN] titik pusat desa (±ratusan meter).
     |  Nama banjar: hanya Desa Dalung yang [RIIL] (sumber: NusaBali &
     |  situs desadalung.badungkab.go.id); sisanya [PERKIRAAN].
     * ================================================================ */

    private function seedWilayah(): array
    {
        $wilayah = [
            'Kuta' => [
                'Kuta'       => ['lat' => -8.7180, 'lng' => 115.1690, 'banjar' => ['Banjar Pande Mas', 'Banjar Buni']],
                'Legian'     => ['lat' => -8.7050, 'lng' => 115.1700, 'banjar' => ['Banjar Legian Kaja', 'Banjar Legian Kelod']],
                'Seminyak'   => ['lat' => -8.6900, 'lng' => 115.1620, 'banjar' => ['Banjar Basangkasa', 'Banjar Seminyak']],
                'Kedonganan' => ['lat' => -8.7525, 'lng' => 115.1720, 'banjar' => ['Banjar Kertha Yasa', 'Banjar Kubu Alit']],
                'Tuban'      => ['lat' => -8.7420, 'lng' => 115.1730, 'banjar' => ['Banjar Tuban Griya', 'Banjar Kelan']],
            ],
            'Kuta Selatan' => [
                'Pecatu'        => ['lat' => -8.8146, 'lng' => 115.1300, 'banjar' => ['Banjar Kangin', 'Banjar Kauh']],
                'Ungasan'       => ['lat' => -8.8200, 'lng' => 115.1600, 'banjar' => ['Banjar Wanagiri', 'Banjar Giri Dharma']],
                'Jimbaran'      => ['lat' => -8.7900, 'lng' => 115.1620, 'banjar' => ['Banjar Ubung', 'Banjar Menega']],
                'Benoa'         => ['lat' => -8.7800, 'lng' => 115.2200, 'banjar' => ['Banjar Peken', 'Banjar Terora']],
                'Tanjung Benoa' => ['lat' => -8.7594, 'lng' => 115.2244, 'banjar' => ['Banjar Panca Bhineka', 'Banjar Purwa Santi']],
            ],
            'Kuta Utara' => [
                'Dalung'      => ['lat' => -8.6270, 'lng' => 115.1770, 'banjar' => [
                    // [RIIL] Banjar-banjar di Desa Dalung yang menjalankan bank sampah.
                    'Banjar Kwanji', 'Banjar Bhineka Nusa Kauh', 'Banjar Taman Tirta',
                    'Banjar Celuk', 'Banjar Campuan Asri Kangin',
                ]],
                'Kerobokan'   => ['lat' => -8.6600, 'lng' => 115.1600, 'banjar' => ['Banjar Taman', 'Banjar Batu Belig']],
                'Tibubeneng'  => ['lat' => -8.6478, 'lng' => 115.1385, 'banjar' => ['Banjar Berawa', 'Banjar Tegal Gundul']],
                'Canggu'      => ['lat' => -8.6450, 'lng' => 115.1330, 'banjar' => ['Banjar Canggu', 'Banjar Padang Linjong']],
                'Tumbak Bayuh' => ['lat' => -8.6300, 'lng' => 115.1450, 'banjar' => ['Banjar Tiying Tutul', 'Banjar Tumbak Bayuh']],
            ],
            'Mengwi' => [
                'Sempidi'   => ['lat' => -8.5850, 'lng' => 115.1760, 'banjar' => ['Banjar Sempidi Kaja', 'Banjar Sempidi Kelod']],
                'Lukluk'    => ['lat' => -8.5900, 'lng' => 115.1850, 'banjar' => ['Banjar Lukluk Kaja', 'Banjar Lukluk Kelod']],
                'Sading'    => ['lat' => -8.5950, 'lng' => 115.1950, 'banjar' => ['Banjar Sading Kaja', 'Banjar Dukuh']],
                'Gulingan'  => ['lat' => -8.5500, 'lng' => 115.1750, 'banjar' => ['Banjar Gulingan Kaja', 'Banjar Gulingan Kelod']],
                'Mengwitani' => ['lat' => -8.5600, 'lng' => 115.1700, 'banjar' => ['Banjar Pande', 'Banjar Alangkajeng']],
            ],
            'Abiansemal' => [
                'Darmasaba'        => ['lat' => -8.5747, 'lng' => 115.1900, 'banjar' => ['Banjar Tegal', 'Banjar Gaji']],
                'Bongkasa Pertiwi' => ['lat' => -8.5100, 'lng' => 115.2400, 'banjar' => ['Banjar Karang Dalem', 'Banjar Tanggayuda']],
                'Mekar Bhuana'     => ['lat' => -8.5600, 'lng' => 115.2200, 'banjar' => ['Banjar Kembang Sari', 'Banjar Pande']],
                'Blahkiuh'         => ['lat' => -8.5100, 'lng' => 115.2100, 'banjar' => ['Banjar Blahkiuh Kaja', 'Banjar Blahkiuh Kelod']],
                'Mambal'           => ['lat' => -8.5400, 'lng' => 115.2300, 'banjar' => ['Banjar Mambal Kajanan', 'Banjar Semana']],
            ],
            'Petang' => [
                'Petang'      => ['lat' => -8.3700, 'lng' => 115.2100, 'banjar' => ['Banjar Petang Kaja', 'Banjar Petang Kelod']],
                'Pelaga'      => ['lat' => -8.3000, 'lng' => 115.2200, 'banjar' => ['Banjar Kiadan', 'Banjar Bukian']],
                'Carangsari'  => ['lat' => -8.4400, 'lng' => 115.2100, 'banjar' => ['Banjar Carangsari', 'Banjar Sekarmukti']],
                'Getasan'     => ['lat' => -8.4200, 'lng' => 115.2050, 'banjar' => ['Banjar Getasan', 'Banjar Tinggan']],
            ],
        ];

        // [RIIL] Camat per Juli 2026 (sumber: pemberitaan resmi 2025).
        //        Nama camat dapat berubah — verifikasi sebelum dipublikasikan.
        $camat = [
            'Kuta'         => 'D. Ngurah Bayudhewa',
            'Kuta Selatan' => 'I Ketut Gede Arta',
            'Kuta Utara'   => 'I Putu Eka Parmana',
            'Mengwi'       => 'I Nyoman Suhartana',
            'Abiansemal'   => null, // belum terverifikasi
            'Petang'       => null, // belum terverifikasi
        ];

        // [RIIL] Perbekel/Lurah yang namanya terverifikasi dari sumber resmi.
        $kepalaWilayah = [
            'Pecatu'        => 'I Made Karyana Yadnya', // Perbekel Pecatu
            'Tanjung Benoa' => 'I Wayan Sudiana',        // Lurah Tanjung Benoa
            'Kedonganan'    => 'I Kadek Laksana',        // Lurah Kedonganan
        ];

        $banjars = [];

        foreach ($wilayah as $namaKec => $daftarDesa) {
            $kec = Kecamatan::firstOrCreate(['nama' => $namaKec]);

            $this->makeUser([
                'name'  => 'Camat ' . $namaKec . ($camat[$namaKec] ? ' — ' . $camat[$namaKec] : ''),
                'email' => 'camat.' . Str::slug($namaKec) . self::MAIL,
                'nip'   => '1976' . str_pad((string) $this->seq, 14, '0', STR_PAD_LEFT),
            ], 'camat', ['kecamatan_id' => $kec->id]);

            foreach ($daftarDesa as $namaKel => $info) {
                $kel = Kelurahan::firstOrCreate(['kecamatan_id' => $kec->id, 'nama' => $namaKel]);

                $pejabat = $kepalaWilayah[$namaKel] ?? null;
                $this->makeUser([
                    'name'  => 'Perbekel/Lurah ' . $namaKel . ($pejabat ? ' — ' . $pejabat : ''),
                    'email' => 'lurah.' . Str::slug($namaKel) . self::MAIL,
                    'nip'   => '1980' . str_pad((string) $this->seq, 14, '0', STR_PAD_LEFT),
                ], 'lurah', ['kecamatan_id' => $kec->id, 'kelurahan_id' => $kel->id]);

                foreach ($info['banjar'] as $namaBanjar) {
                    $banjar = BanjarDinas::firstOrCreate([
                        'kelurahan_id' => $kel->id,
                        'nama'         => $namaBanjar,
                    ]);

                    $this->makeUser([
                        'name'  => 'Kelian Dinas ' . $namaBanjar,
                        'email' => 'kelian.' . Str::slug($namaBanjar) . '.' . $this->seq . self::MAIL,
                        'nip'   => '1985' . str_pad((string) $this->seq, 14, '0', STR_PAD_LEFT),
                    ], 'kepala_dinas_banjar', [
                        'kecamatan_id' => $kec->id,
                        'kelurahan_id' => $kel->id,
                        'banjar_id'    => $banjar->id,
                    ]);

                    // simpan koordinat desa induk untuk dipakai entitas lain
                    $banjars[] = ['model' => $banjar, 'lat' => $info['lat'], 'lng' => $info['lng'], 'desa' => $namaKel];
                }
            }
        }

        return $banjars;
    }

    /* ================================================================
     |  EKSEKUTIF — [RIIL] Bupati & Wakil Bupati Badung periode 2025–2030
     |  (ditetapkan KPU Badung, Januari 2025).
     * ================================================================ */

    private function seedEksekutif(): void
    {
        $this->makeUser([
            'name'  => 'I Wayan Adi Arnawa — Bupati Badung',
            'email' => 'bupati' . self::MAIL,
            'nip'   => '1970' . str_pad((string) $this->seq, 14, '0', STR_PAD_LEFT),
        ], 'bupati');

        $this->makeUser([
            'name'  => 'Bagus Alit Sucipta — Wakil Bupati Badung',
            'email' => 'wakil.bupati' . self::MAIL,
            'nip'   => '1974' . str_pad((string) $this->seq, 14, '0', STR_PAD_LEFT),
        ], 'bupati');

        // Dinas Lingkungan Hidup dan Kebersihan (DLHK) Kabupaten Badung.
        $this->makeUser([
            'name'  => 'Admin DLHK Kabupaten Badung',
            'email' => 'dlhk' . self::MAIL,
            'nip'   => '1982' . str_pad((string) $this->seq, 14, '0', STR_PAD_LEFT),
        ], 'admin_dinas');

        $this->makeUser([
            'name'  => 'Administrator Niti Resik',
            'email' => 'admin' . self::MAIL,
        ], 'admin');
    }

    /* ================================================================
     |  MASTER DATA
     |  Harga sampah: [PERKIRAAN] kisaran pasar rongsok Bali 2025–2026.
     |  Wajib disesuaikan dengan daftar harga resmi DLHK/bank sampah.
     * ================================================================ */

    private function seedMasterData(): array
    {
        $prices = [
            ['Botol Plastik PET Bening', 4000],
            ['Botol Plastik PET Warna', 2500],
            ['Gelas Plastik (Cup)', 3500],
            ['Kardus / Karton', 2200],
            ['Kertas HVS / Buku', 2800],
            ['Koran Bekas', 1800],
            ['Kaleng Aluminium', 12000],
            ['Besi Tua', 4500],
            ['Botol Kaca', 700],
            ['Plastik Kresek / Bungkus', 1200],
            ['Tembaga', 60000],
            ['Minyak Jelantah', 6000],
            ['Aki Bekas', 15000],
            ['Elektronik Kecil (E-waste)', 8000],
        ];

        $out = [];
        foreach ($prices as [$nama, $harga]) {
            $out[] = WastePrice::firstOrCreate(
                ['jenis_sampah' => $nama],
                ['satuan' => 'kg', 'harga_per_kg' => $harga, 'is_active' => true]
            );
        }

        foreach (['Pupuk & Kompos', 'Kerajinan', 'Fesyen Daur Ulang', 'Rumah Tangga', 'Aksesori', 'Ecobrick'] as $c) {
            ProductCategory::firstOrCreate(['nama' => $c]);
        }

        // Kategori laporan mengacu pada persoalan sampah nyata di Badung.
        $rc = [
            'Pembuangan sampah liar',
            'Pembakaran sampah terbuka',
            'Sampah menumpuk di TPS',
            'Saluran/got tersumbat sampah',
            'Sampah di sungai / pantai',
            'Sampah dari usaha/akomodasi',
            'Truk pengangkut tidak datang',
        ];
        foreach ($rc as $c) {
            ReportCategory::firstOrCreate(['nama' => $c]);
        }

        return $out;
    }

    /* ================================================================
     |  WARGA & PETUGAS LAPANGAN — [FIKTIF]
     |  Nama Bali dibuat menyerupai aslinya, tetapi bukan orang nyata.
     * ================================================================ */

    private function namaBali(): array
    {
        $depan  = ['I Wayan', 'I Made', 'I Nyoman', 'I Ketut', 'I Gede', 'I Putu', 'I Komang', 'I Kadek'];
        $depanP = ['Ni Wayan', 'Ni Made', 'Ni Nyoman', 'Ni Ketut', 'Ni Luh', 'Ni Putu', 'Ni Komang', 'Ni Kadek'];
        $belakang = [
            'Sudiarta', 'Astawa', 'Suparta', 'Widiana', 'Mahendra', 'Sukarma', 'Adnyana', 'Wirawan',
            'Suryani', 'Ardani', 'Parwati', 'Sumerta', 'Yuliana', 'Darmawan', 'Antara', 'Purnama',
            'Sudarsana', 'Arimbawa', 'Juniarta', 'Sriasih', 'Wibawa', 'Trisnadi', 'Karmila', 'Pastika',
        ];

        $lk = $this->faker->boolean();
        $nama = $this->faker->randomElement($lk ? $depan : $depanP) . ' ' . $this->faker->randomElement($belakang);

        return [$nama, $lk ? 'L' : 'P'];
    }

    private function seedWarga(array $banjars): array
    {
        $masyarakat = [];

        for ($i = 0; $i < 40; $i++) {
            $b = $this->faker->randomElement($banjars);
            [$nama, $jk] = $this->namaBali();

            $u = $this->makeUser(array_merge([
                'name'              => $nama,
                'email'             => 'warga' . ($i + 1) . self::MAIL,
                'jenis_kelamin'     => $jk,
                'tanggal_lahir'     => $this->faker->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
                'nik'               => '5103' . $this->faker->numerify('############'),
                'phone'             => '628' . $this->faker->numerify('##########'),
                'phone_verified_at' => now(),
                'kode_qr'           => 'NR' . str_pad((string) ($i + 1), 8, '0', STR_PAD_LEFT),
            ], $this->near($b['lat'], $b['lng'])), 'masyarakat', [
                'kecamatan_id' => $b['model']->kelurahan->kecamatan_id,
                'kelurahan_id' => $b['model']->kelurahan_id,
                'banjar_id'    => $b['model']->id,
            ]);

            Wallet::create(['user_id' => $u->id, 'saldo' => 0]);
            $masyarakat[] = $u;
        }

        // Petugas lapangan DLHK — [FIKTIF], ditempatkan per wilayah kerja.
        $wilayahKerja = ['Kuta', 'Kuta Selatan', 'Kuta Utara', 'Mengwi', 'Abiansemal', 'Petang'];
        $petugasLapangan = [];

        foreach ($wilayahKerja as $i => $wil) {
            [$nama] = $this->namaBali();
            $petugasLapangan[] = $this->makeUser([
                'name'  => $nama,
                'email' => 'lapangan.' . Str::slug($wil) . self::MAIL,
            ], 'petugas_lapangan');
        }

        return [$masyarakat, $petugasLapangan];
    }

    /* ================================================================
     |  BANK SAMPAH
     |  [RIIL] Program bank sampah Kab. Badung: Bank Sampah Mandiri (BSM)
     |         PKK "Mangu Srikandi" dan Bank Sampah Edukasi Badung (BSEB)
     |         "Mangu Kumara" untuk sekolah — dicanangkan lewat program
     |         Gertak Badung Bersih.
     |  [RIIL] Banjar di Desa Dalung yang aktif menjalankan bank sampah:
     |         Kwanji, Bhineka Nusa Kauh, Taman Tirta, Celuk, Campuan
     |         Asri Kangin (sumber: NusaBali / situs desa Dalung).
     |  [PERKIRAAN] Alamat, no. telepon, dan koordinat presisi.
     * ================================================================ */

    private function seedBankSampah(): array
    {
        $daftar = [
            [
                'nama'   => 'BSM Mangu Srikandi Banjar Kwanji',
                'desa'   => 'Dalung',
                'alamat' => 'Banjar Kwanji, Desa Dalung, Kec. Kuta Utara, Kab. Badung',
                'lat'    => -8.6255, 'lng' => 115.1742,
            ],
            [
                'nama'   => 'BSM Mangu Srikandi Banjar Bhineka Nusa Kauh',
                'desa'   => 'Dalung',
                'alamat' => 'Banjar Bhineka Nusa Kauh, Desa Dalung, Kec. Kuta Utara, Kab. Badung',
                'lat'    => -8.6288, 'lng' => 115.1795,
            ],
            [
                'nama'   => 'BSM Mangu Srikandi Banjar Taman Tirta',
                'desa'   => 'Dalung',
                'alamat' => 'Banjar Taman Tirta, Desa Dalung, Kec. Kuta Utara, Kab. Badung',
                'lat'    => -8.6301, 'lng' => 115.1758,
            ],
            [
                'nama'   => 'BSM Mangu Srikandi Banjar Campuan Asri Kangin',
                'desa'   => 'Dalung',
                'alamat' => 'Banjar Campuan Asri Kangin, Desa Dalung, Kec. Kuta Utara, Kab. Badung',
                'lat'    => -8.6242, 'lng' => 115.1811,
            ],
            [
                'nama'   => 'BSM Mangu Srikandi Desa Tumbak Bayuh',
                'desa'   => 'Tumbak Bayuh',
                'alamat' => 'Banjar Tiying Tutul, Desa Tumbak Bayuh, Kec. Mengwi, Kab. Badung',
                'lat'    => -8.6312, 'lng' => 115.1463,
            ],
            [
                'nama'   => 'BSM Mangu Srikandi Kelurahan Sempidi',
                'desa'   => 'Sempidi',
                'alamat' => 'Kelurahan Sempidi, Kec. Mengwi, Kab. Badung',
                'lat'    => -8.5861, 'lng' => 115.1773,
            ],
            [
                'nama'   => 'BSEB Mangu Kumara Kecamatan Kuta',
                'desa'   => 'Kuta',
                'alamat' => 'Kelurahan Kuta, Kec. Kuta, Kab. Badung (bank sampah edukasi sekolah)',
                'lat'    => -8.7192, 'lng' => 115.1704,
            ],
        ];

        $bankSampahs = [];
        $petugasBS = [];

        foreach ($daftar as $i => $d) {
            $kel = Kelurahan::where('nama', $d['desa'])->first();
            $banjar = $kel ? BanjarDinas::where('kelurahan_id', $kel->id)->first() : null;

            $bs = BankSampah::create([
                'nama'      => $d['nama'],
                'alamat'    => $d['alamat'],
                'no_hp'     => '0361' . $this->faker->numerify('######'),
                'banjar_id' => $banjar?->id,
                'lat'       => $d['lat'],
                'lng'       => $d['lng'],
            ]);

            // Admin & petugas bank sampah — [FIKTIF]
            [$namaAdmin] = $this->namaBali();
            $this->makeUser([
                'name'  => $namaAdmin,
                'email' => 'adminbs' . ($i + 1) . self::MAIL,
            ], 'admin_bank_sampah', ['bank_sampah_id' => $bs->id]);

            $ps = [];
            for ($p = 0; $p < 2; $p++) {
                [$namaPetugas] = $this->namaBali();
                $ps[] = $this->makeUser([
                    'name'  => $namaPetugas,
                    'email' => 'petugasbs' . ($i + 1) . '-' . ($p + 1) . self::MAIL,
                ], 'petugas_bank_sampah', ['bank_sampah_id' => $bs->id]);
            }

            $bankSampahs[] = $bs;
            $petugasBS[$bs->id] = $ps;
        }

        return [$bankSampahs, $petugasBS];
    }

    /* ================================================================
     |  TPS3R — [RIIL] Fasilitas TPS3R Kabupaten Badung yang terverifikasi
     |  dari situs resmi Pemkab Badung & pemberitaan 2025–2026:
     |   • TPS3R Pecatu (Kuta Selatan) — timbulan masuk ±30 ton/hari,
     |     kapasitas olah 5–7 ton/hari (pemantauan Bupati, Feb 2025)
     |   • TPS3R Panca Lestari, Tanjung Benoa (Kuta Selatan)
     |   • TPS3R Kedonganan (Kuta) — diresmikan Februari 2022
     |   • TPS3R Abirupa Pertiwi, Desa Bongkasa Pertiwi (Abiansemal)
     |     — ditinjau Menteri LH bersama Gubernur Bali, Maret 2026
     |   • TPS3R Pudak Mesari, Desa Darmasaba (Abiansemal) — idem
     |   • TPS3R Bhakti Pertiwi, Desa Mekar Bhuana (Abiansemal)
     |  [PERKIRAAN] koordinat presisi, tarif, dan no. telepon.
     * ================================================================ */

    private function seedTps(array $masyarakat): void
    {
        $daftar = [
            [
                'nama' => 'TPS3R Pecatu', 'desa' => 'Pecatu',
                'alamat' => 'Desa Pecatu, Kec. Kuta Selatan, Kab. Badung',
                'lat' => -8.8134, 'lng' => 115.1322,
                'berbayar' => true, 'tarif' => 25000,
            ],
            [
                'nama' => 'TPS3R Panca Lestari', 'desa' => 'Tanjung Benoa',
                'alamat' => 'Kelurahan Tanjung Benoa, Kec. Kuta Selatan, Kab. Badung',
                'lat' => -8.7601, 'lng' => 115.2231,
                'berbayar' => true, 'tarif' => 20000,
            ],
            [
                'nama' => 'TPS3R Kedonganan', 'desa' => 'Kedonganan',
                'alamat' => 'Kelurahan Kedonganan, Kec. Kuta, Kab. Badung',
                'lat' => -8.7538, 'lng' => 115.1737,
                'berbayar' => true, 'tarif' => 20000,
            ],
            [
                'nama' => 'TPS3R Abirupa Pertiwi', 'desa' => 'Bongkasa Pertiwi',
                'alamat' => 'Desa Bongkasa Pertiwi, Kec. Abiansemal, Kab. Badung',
                'lat' => -8.5112, 'lng' => 115.2415,
                'berbayar' => true, 'tarif' => 15000,
            ],
            [
                'nama' => 'TPS3R Pudak Mesari', 'desa' => 'Darmasaba',
                'alamat' => 'Desa Darmasaba, Kec. Abiansemal, Kab. Badung',
                'lat' => -8.5759, 'lng' => 115.1913,
                'berbayar' => true, 'tarif' => 15000,
            ],
            [
                'nama' => 'TPS3R Bhakti Pertiwi', 'desa' => 'Mekar Bhuana',
                'alamat' => 'Desa Mekar Bhuana, Kec. Abiansemal, Kab. Badung',
                'lat' => -8.5613, 'lng' => 115.2214,
                'berbayar' => false, 'tarif' => null,
            ],
        ];

        $tpsList = [];

        foreach ($daftar as $i => $d) {
            $kel = Kelurahan::where('nama', $d['desa'])->first();
            $banjar = $kel ? BanjarDinas::where('kelurahan_id', $kel->id)->first() : null;

            $tps = Tps::create([
                'nama'        => $d['nama'],
                'alamat'      => $d['alamat'],
                'no_hp'       => '0361' . $this->faker->numerify('######'),
                'is_berbayar' => $d['berbayar'],
                'tarif'       => $d['tarif'],
                'banjar_id'   => $banjar?->id,
                'lat'         => $d['lat'],
                'lng'         => $d['lng'],
            ]);

            // Pengelola TPS — [FIKTIF]
            [$nama] = $this->namaBali();
            $this->makeUser([
                'name'  => $nama,
                'email' => 'admintps' . ($i + 1) . self::MAIL,
            ], 'admin_tps', ['tps_id' => $tps->id]);

            $tpsList[] = $tps;
        }

        // Langganan warga ke TPS
        foreach (collect($masyarakat)->random(24) as $warga) {
            $tps = $this->faker->randomElement($tpsList);

            $member = TpsMember::firstOrCreate(
                ['tps_id' => $tps->id, 'user_id' => $warga->id],
                ['status' => 'aktif', 'joined_at' => now()->subDays($this->faker->numberBetween(30, 400))]
            );

            if (! $tps->is_berbayar) {
                continue;
            }

            foreach ([now()->subMonths(2), now()->subMonth(), now()] as $bulan) {
                $lunas = $this->faker->boolean(72);
                $metode = $this->faker->randomElement(['saldo', 'midtrans']);

                TpsSubscription::create([
                    'periode'       => $bulan->format('Y-m'),
                    'jumlah'        => $tps->tarif,
                    'status'        => $lunas ? 'lunas' : 'menunggu',
                    'metode_bayar'  => $lunas ? $metode : null,
                    'paid_at'       => $lunas ? $bulan : null,
                    'tps_member_id' => $member->id,
                ]);

                if ($lunas && $metode === 'saldo') {
                    $this->wallet($warga, -$tps->tarif, 'belanja', $tps, 'Iuran ' . $tps->nama . ' ' . $bulan->format('M Y'));
                }
            }
        }
    }

    /* ================================================================
     |  UMKM — [FIKTIF]
     |  Nama usaha & produk sengaja dikarang. UMKM adalah usaha privat;
     |  memakai nama usaha nyata beserta omzet karangan tidak pantas.
     |  Jenis produknya mengacu pada praktik daur ulang nyata di Badung
     |  (pupuk dari olahan organik TPS3R, ecobrick, kerajinan plastik).
     * ================================================================ */

    private function seedUmkm(): array
    {
        $categories = ProductCategory::pluck('id', 'nama')->all();

        $daftar = [
            [
                'nama' => 'Kompos Werdhi Guna', 'desa' => 'Darmasaba',
                'deskripsi' => 'Produsen pupuk kompos dari olahan sampah organik TPS3R desa.',
                'produk' => [
                    ['Pupuk Kompos Organik 5 kg', 'Pupuk & Kompos', 25000, 3, 5000],
                    ['Pupuk Cair Bio 1 Liter', 'Pupuk & Kompos', 35000, 1, 1200],
                    ['Media Tanam Premium 10 kg', 'Pupuk & Kompos', 45000, 2, 10000],
                ],
            ],
            [
                'nama' => 'Ecobrick Bali Kreasi', 'desa' => 'Dalung',
                'deskripsi' => 'Furnitur sederhana berbahan ecobrick hasil setoran bank sampah banjar.',
                'produk' => [
                    ['Kursi Ecobrick', 'Ecobrick', 150000, 4, 4000],
                    ['Pot Tanaman Ecobrick', 'Ecobrick', 45000, 6, 1500],
                    ['Meja Kecil Ecobrick', 'Ecobrick', 275000, 2, 7000],
                ],
            ],
            [
                'nama' => 'Rajut Sari Craft', 'desa' => 'Kerobokan',
                'deskripsi' => 'Kerajinan rajut dari limbah kemasan plastik oleh ibu-ibu PKK.',
                'produk' => [
                    ['Tas Rajut Plastik', 'Kerajinan', 85000, 8, 400],
                    ['Dompet Daur Ulang', 'Kerajinan', 45000, 12, 150],
                    ['Topi Anyaman Plastik', 'Fesyen Daur Ulang', 65000, 5, 250],
                ],
            ],
            [
                'nama' => 'Loka Recycle Jimbaran', 'desa' => 'Jimbaran',
                'deskripsi' => 'Produk fungsional dari kain perca dan ban bekas.',
                'produk' => [
                    ['Tote Bag Kanvas Daur Ulang', 'Fesyen Daur Ulang', 55000, 15, 300],
                    ['Sandal Ban Bekas', 'Rumah Tangga', 60000, 10, 800],
                    ['Tempat Pensil Perca', 'Aksesori', 25000, 20, 120],
                ],
            ],
            [
                'nama' => 'Griya Daur Mengwi', 'desa' => 'Sempidi',
                'deskripsi' => 'Kerajinan dekorasi rumah dari botol kaca dan limbah kayu.',
                'produk' => [
                    ['Lampu Hias Botol Kaca', 'Kerajinan', 120000, 6, 900],
                    ['Vas Bunga Kaca Ukir', 'Rumah Tangga', 75000, 8, 700],
                    ['Gantungan Kunci Kayu', 'Aksesori', 15000, 30, 50],
                ],
            ],
            [
                'nama' => 'Sari Lestari Ecosoap', 'desa' => 'Tumbak Bayuh',
                'deskripsi' => 'Sabun dan lilin dari minyak jelantah hasil setoran warga.',
                'produk' => [
                    ['Sabun Cuci Minyak Jelantah', 'Rumah Tangga', 20000, 25, 250],
                    ['Lilin Aromaterapi Jelantah', 'Rumah Tangga', 35000, 14, 300],
                    ['Cairan Pembersih Eco 500ml', 'Rumah Tangga', 28000, 18, 550],
                ],
            ],
        ];

        $umkms = [];
        $products = [];

        foreach ($daftar as $i => $d) {
            $kel = Kelurahan::where('nama', $d['desa'])->first();
            $banjar = $kel ? BanjarDinas::where('kelurahan_id', $kel->id)->first() : null;

            // koordinat sekitar desa
            $pusat = ['Darmasaba' => [-8.5747, 115.1900], 'Dalung' => [-8.6270, 115.1770],
                      'Kerobokan' => [-8.6600, 115.1600], 'Jimbaran' => [-8.7900, 115.1620],
                      'Sempidi' => [-8.5850, 115.1760], 'Tumbak Bayuh' => [-8.6300, 115.1450]][$d['desa']];

            $umkm = Umkm::create(array_merge([
                'nama'      => $d['nama'],
                'deskripsi' => $d['deskripsi'],
                'alamat'    => 'Desa ' . $d['desa'] . ', Kab. Badung',
                'no_hp'     => '08' . $this->faker->numerify('##########'),
                'banjar_id' => $banjar?->id,
                'status'    => 'aktif',
            ], $this->near($pusat[0], $pusat[1], 0.002)));

            [$namaPemilik] = $this->namaBali();
            $this->makeUser([
                'name'  => $namaPemilik,
                'email' => 'umkm' . ($i + 1) . self::MAIL,
            ], 'umkm', ['umkm_id' => $umkm->id]);

            foreach ($d['produk'] as [$namaProduk, $kategori, $harga, $stokFaktor, $berat]) {
                $product = Product::create([
                    'umkm_id'     => $umkm->id,
                    'kategori_id' => $categories[$kategori] ?? array_values($categories)[0],
                    'nama'        => $namaProduk,
                    'deskripsi'   => 'Produk daur ulang buatan ' . $d['nama'] . ' — ' . strtolower($d['deskripsi']),
                    'harga'       => $harga,
                    'stok'        => $this->faker->numberBetween($stokFaktor, $stokFaktor * 8),
                    'berat'       => $berat,
                    'is_active'   => true,
                ]);

                for ($img = 0; $img < 2; $img++) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'path'       => 'products/placeholder-' . $this->faker->numberBetween(1, 8) . '.jpg',
                    ]);
                }

                $products[] = $product;
            }

            $umkms[] = $umkm;
        }

        return [$umkms, $products];
    }

    /* ================================================================
     |  TRANSAKSI — [FIKTIF] (pola dipertahankan dari DemoSeeder)
     * ================================================================ */

    private function seedDeposits(array $bankSampahs, array $petugasBS, array $masyarakat, array $wastePrices): void
    {
        foreach ($masyarakat as $warga) {
            for ($d = 0, $n = $this->faker->numberBetween(1, 5); $d < $n; $d++) {
                $bs = $this->faker->randomElement($bankSampahs);
                $petugas = $this->faker->randomElement($petugasBS[$bs->id]);
                $tanggal = now()->subDays($this->faker->numberBetween(0, 120));

                $deposit = WasteDeposit::create([
                    'bank_sampah_id' => $bs->id,
                    'petugas_id'     => $petugas->id,
                    'nasabah_id'     => $warga->id,
                    'total_berat'    => 0,
                    'total_nilai'    => 0,
                    'status'         => 'selesai',
                    'created_at'     => $tanggal,
                    'updated_at'     => $tanggal,
                ]);

                $totalBerat = 0;
                $totalNilai = 0;

                foreach (collect($wastePrices)->random($this->faker->numberBetween(1, 3)) as $price) {
                    $berat = $this->faker->randomFloat(2, 0.5, 8);
                    $sub = round($berat * (float) $price->harga_per_kg, 2);

                    WasteDepositItem::create([
                        'deposit_id'     => $deposit->id,
                        'waste_price_id' => $price->id,
                        'berat'          => $berat,
                        'harga_snapshot' => $price->harga_per_kg,
                        'subtotal'       => $sub,
                    ]);

                    $totalBerat += $berat;
                    $totalNilai += $sub;
                }

                $deposit->update(['total_berat' => $totalBerat, 'total_nilai' => $totalNilai]);
                $this->wallet($warga, $totalNilai, 'setor', $deposit, 'Setor sampah di ' . $bs->nama);
            }
        }
    }

    private function seedWithdrawals(array $masyarakat): void
    {
        $kandidat = collect($masyarakat)->filter(fn ($u) => ($this->saldo[$u->id] ?? 0) > 60000)->values();

        foreach ($kandidat->random(min(12, $kandidat->count())) as $warga) {
            $maks = (float) ($this->saldo[$warga->id] ?? 0);
            $jumlah = min($maks, $this->faker->randomElement([50000, 75000, 100000, 150000]));

            if ($jumlah < 50000) {
                continue;
            }

            $status = $this->faker->randomElement(['menunggu', 'menunggu', 'disetujui', 'ditolak', 'selesai']);

            $w = Withdrawal::create([
                'user_id'     => $warga->id,
                'jumlah'      => $jumlah,
                'metode'      => 'transfer_bank',
                'no_rekening' => $this->faker->randomElement(['BPD Bali ', 'BNI ', 'BRI ']) . $this->faker->numerify('##########'),
                'status'      => $status,
                'approved_by' => null,
                'catatan'     => $status === 'ditolak' ? 'Nama rekening tidak sesuai identitas nasabah.' : null,
            ]);

            if (in_array($status, ['disetujui', 'selesai'])) {
                $this->wallet($warga, -$jumlah, 'penarikan', $w, 'Penarikan saldo #' . $w->id);
            }
        }
    }

    private function seedOrders(array $masyarakat, array $umkms, array $products): void
    {
        $byUmkm = collect($products)->groupBy('umkm_id');

        for ($i = 0; $i < 25; $i++) {
            $umkm = $this->faker->randomElement($umkms);
            $daftarProduk = $byUmkm->get($umkm->id);

            if (! $daftarProduk || $daftarProduk->isEmpty()) {
                continue;
            }

            $buyer = $this->faker->randomElement($masyarakat);
            $pilih = $daftarProduk->random($this->faker->numberBetween(1, min(3, $daftarProduk->count())));
            $ongkir = $this->faker->randomElement([10000, 12000, 15000, 20000]);

            $subtotal = 0;
            $rows = [];
            foreach ($pilih as $p) {
                $qty = $this->faker->numberBetween(1, 3);
                $sub = $qty * (float) $p->harga;
                $subtotal += $sub;
                $rows[] = ['product' => $p, 'qty' => $qty, 'sub' => $sub];
            }
            $total = $subtotal + $ongkir;

            $metode = $this->faker->randomElement(['saldo', 'midtrans']);
            $status = $this->faker->randomElement(['menunggu_bayar', 'dibayar', 'dikemas', 'dikirim', 'selesai', 'selesai']);
            $dibayar = $status !== 'menunggu_bayar';

            if ($metode === 'saldo' && $dibayar && ($this->saldo[$buyer->id] ?? 0) < $total) {
                $status = 'menunggu_bayar';
                $dibayar = false;
            }

            $order = Order::create([
                'user_id'        => $buyer->id,
                'umkm_id'        => $umkm->id,
                'total'          => $total,
                'ongkir'         => $ongkir,
                'metode_bayar'   => $metode,
                'status'         => $status,
                'alamat_kirim'   => $this->faker->streetAddress() . ', Kab. Badung, Bali',
                'destination_id' => $this->faker->numberBetween(1000, 9999),
                'kurir'          => in_array($status, ['dikirim', 'selesai']) ? $this->faker->randomElement(['jne', 'sicepat', 'anteraja']) : null,
                'no_resi'        => in_array($status, ['dikirim', 'selesai']) ? strtoupper($this->faker->bothify('??########')) : null,
            ]);

            foreach ($rows as $r) {
                OrderItem::create([
                    'order_id'       => $order->id,
                    'product_id'     => $r['product']->id,
                    'nama_snapshot'  => $r['product']->nama,
                    'harga_snapshot' => $r['product']->harga,
                    'qty'            => $r['qty'],
                    'subtotal'       => $r['sub'],
                ]);
            }

            Payment::create([
                'payable_type'            => $order->getMorphClass(),
                'payable_id'              => $order->id,
                'metode'                  => $metode,
                'midtrans_order_id'       => $metode === 'midtrans' ? 'ORD-' . $order->id . '-' . Str::upper(Str::random(6)) : null,
                'midtrans_transaction_id' => $metode === 'midtrans' && $dibayar ? (string) Str::uuid() : null,
                'amount'                  => $total,
                'status'                  => $dibayar ? 'paid' : 'pending',
                'paid_at'                 => $dibayar ? now()->subDays($this->faker->numberBetween(0, 20)) : null,
            ]);

            if ($dibayar && $metode === 'saldo') {
                $this->wallet($buyer, -$total, 'belanja', $order, 'Belanja di ' . $umkm->nama);
            }
        }
    }

    /* ================================================================
     |  LAPORAN — [FIKTIF], tetapi judul & lokasi mengacu pada persoalan
     |  sampah nyata di Badung (TPA Suwung, sampah kiriman pantai, dll).
     * ================================================================ */

    private function seedReports(array $masyarakat, array $petugasLapangan, array $banjars): void
    {
        $kategori = ReportCategory::pluck('id')->all();

        $contoh = [
            'Tumpukan sampah di bahu Jalan Raya Kerobokan',
            'Pembakaran sampah plastik dekat permukiman warga',
            'Got tersumbat sampah kemasan saat hujan',
            'Sampah kiriman menumpuk di bibir pantai',
            'TPS penuh, sampah meluber ke jalan',
            'Pembuangan sampah liar di lahan kosong',
            'Sampah sisa upacara belum terangkut',
            'Truk pengangkut belum datang tiga hari',
        ];

        for ($i = 0; $i < 24; $i++) {
            $pelapor = $this->faker->randomElement($masyarakat);
            $b = $this->faker->randomElement($banjars);
            $c = $this->near($b['lat'], $b['lng']);
            $status = $this->faker->randomElement(['menunggu', 'diverifikasi', 'ditugaskan', 'proses', 'selesai', 'selesai', 'ditolak']);
            $tanggal = now()->subDays($this->faker->numberBetween(0, 75));

            $report = Report::create([
                'pelapor_id'  => $pelapor->id,
                'kategori_id' => $this->faker->randomElement($kategori),
                'tiket_no'    => 'RPT-' . $tanggal->format('Ym') . '-' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'judul'       => $this->faker->randomElement($contoh),
                'deskripsi'   => $this->faker->paragraph(3),
                'lat'         => $c['lat'],
                'lng'         => $c['lng'],
                'alamat'      => $b['model']->nama . ', Desa/Kel. ' . $b['desa'] . ', Kab. Badung',
                'banjar_id'   => $b['model']->id,
                'status'      => $status,
                'is_duplikat' => false,
                'verified_by' => null,
                'created_at'  => $tanggal,
                'updated_at'  => $tanggal,
            ]);

            if (! in_array($status, ['ditugaskan', 'proses', 'selesai'])) {
                continue;
            }

            $petugas = $this->faker->randomElement($petugasLapangan);

            ReportAssignment::create([
                'report_id'   => $report->id,
                'petugas_id'  => $petugas->id,
                'assigned_by' => null,
                'status'      => $status === 'selesai' ? 'selesai' : ($status === 'proses' ? 'dikerjakan' : 'ditugaskan'),
                'assigned_at' => (clone $tanggal)->addHours($this->faker->numberBetween(2, 48)),
            ]);

            if (in_array($status, ['proses', 'selesai'])) {
                ReportProgress::create([
                    'report_id'       => $report->id,
                    'petugas_id'      => $petugas->id,
                    'catatan'         => $status === 'selesai'
                        ? 'Sampah sudah diangkut, area dibersihkan, residu dikirim sesuai prosedur.'
                        : 'Sedang ditangani, pengangkutan dijadwalkan hari ini.',
                    'foto_bukti'      => 'reports/bukti-' . $this->faker->numberBetween(1, 6) . '.jpg',
                    'status_progress' => $status === 'selesai' ? 'selesai' : 'dikerjakan',
                    'lat'             => $c['lat'],
                    'lng'             => $c['lng'],
                ]);
            }
        }
    }

    /* ================================================================
     |  MISC — klasifikasi AI, artikel edukasi, notifikasi
     * ================================================================ */

    private function seedMisc(array $masyarakat): void
    {
        $jenis = ['Botol Plastik PET Bening', 'Kardus / Karton', 'Kaleng Aluminium', 'Organik', 'Botol Kaca'];

        for ($i = 0; $i < 15; $i++) {
            $u = $this->faker->randomElement($masyarakat);
            WasteClassification::create([
                'user_id'                => $u->id,
                'image_path'             => 'classifications/sample-' . $this->faker->numberBetween(1, 10) . '.jpg',
                'hasil_jenis'            => $this->faker->randomElement($jenis),
                'kategori'               => $this->faker->randomElement(['Anorganik', 'Organik', 'B3']),
                'confidence'             => $this->faker->randomFloat(3, 0.6, 0.99),
                'langkah_pengolahan'     => ['Bersihkan sisa isi', 'Keringkan', 'Pipihkan agar hemat ruang', 'Setor ke bank sampah terdekat'],
                'rekomendasi_daur_ulang' => 'Dapat disetor ke bank sampah banjar untuk ditukar menjadi saldo.',
                'raw_response'           => ['label' => 'sample', 'score' => 0.9],
            ]);
        }

        $this->seedArticles();

        foreach (collect($masyarakat)->random(25) as $u) {
            Notification::create([
                'user_id' => $u->id,
                'tipe'    => 'setor_sampah',
                'channel' => 'inapp',
                'pesan'   => 'Saldo Anda bertambah dari setoran sampah terbaru.',
                'status'  => $this->faker->randomElement(['terkirim', 'dibaca']),
                'read_at' => $this->faker->boolean(50) ? now() : null,
            ]);
        }
    }

    /* ================================================================
     |  ARTIKEL EDUKASI — KONTEN RIIL
     |  ----------------------------------------------------------------
     |  Seluruh angka, regulasi, dan rujukan di bawah bersumber dari
     |  dokumen/pemberitaan resmi dan jurnal yang dapat ditelusuri.
     |  Setiap artikel diakhiri blok <div class="ref"> berisi daftar
     |  rujukan agar pembaca dapat memverifikasi sendiri.
     |
     |  PENTING sebelum publikasi:
     |  • Tautan YouTube di bawah diperoleh dari hasil penelusuran web
     |    dan BELUM ditonton/divalidasi isinya satu per satu. Tonton
     |    dulu, pastikan relevan dan izin penyematannya terbuka.
     |  • Angka statistik berubah tiap tahun — perbarui bila sudah ada
     |    rilis terbaru dari DLHK Badung / SIPSN / BPS.
     |
     |  Tipe yang dipakai menyesuaikan enum `articles.tipe` yang ada:
     |  'artikel', 'panduan', 'tutorial', 'jurnal'. Materi video memakai
     |  tipe 'tutorial' karena enum tidak memuat 'video'.
     * ================================================================ */

    private function seedArticles(): void
    {
        $penulis = User::role('admin_dinas')->first() ?? User::role('admin')->first();

        $artikel = [

            /* ---------------- PANDUAN ---------------- */
            [
                'tipe'  => 'panduan',
                'judul' => 'Cara Memilah Sampah Rumah Tangga: Tiga Wadah, Satu Kebiasaan',
                'thumb' => 'articles/pilah-sampah.jpg',
                'hari'  => 120,
                'konten' => <<<'HTML'
<p>Memilah sampah bukan pekerjaan tambahan — ia hanya soal memindahkan keputusan ke depan. Alih-alih mencampur semua lalu menyesal di TPA, kita memutuskan sejak di dapur.</p>

<h2>Kenapa harus dipilah?</h2>
<p>Data Dinas Lingkungan Hidup Provinsi Bali menunjukkan dari sekitar <strong>4.281 ton sampah per hari</strong> yang dihasilkan Bali, baru sekitar <strong>48% yang tertangani dengan baik</strong>. Sisanya dibuang sembarangan, dibakar, atau berakhir di sungai. Komposisinya didominasi sampah organik (±60%), disusul anorganik (±30%) dan residu (±10%).</p>
<p>Artinya, lebih dari separuh persoalan sampah Bali sebenarnya <em>bisa</em> diselesaikan di rumah — karena organik dapat dikompos dan anorganik dapat disetor ke bank sampah.</p>

<h2>Tiga wadah yang perlu disiapkan</h2>
<ol>
  <li><strong>Organik (hijau)</strong> — sisa makanan, kulit buah, daun kering, sisa canang. Bisa dikompos sendiri atau diserahkan ke TPS3R.</li>
  <li><strong>Anorganik (biru)</strong> — botol plastik, kardus, kertas, kaleng, botol kaca. Inilah yang punya nilai jual di bank sampah.</li>
  <li><strong>Residu (hitam)</strong> — popok, pembalut, styrofoam, kemasan multilayer. Belum dapat didaur ulang, harus diangkut petugas.</li>
</ol>

<h2>Empat langkah agar setoran diterima</h2>
<ol>
  <li><strong>Kosongkan isinya.</strong> Botol bekas minuman manis yang tidak dibilas akan mengundang semut dan menurunkan harga.</li>
  <li><strong>Bilas dan keringkan.</strong> Sampah basah menambah berat, tetapi menurunkan mutu dan bisa ditolak.</li>
  <li><strong>Pipihkan.</strong> Botol dan kardus yang dipipihkan menghemat ruang penyimpanan sampai tiga kali lipat.</li>
  <li><strong>Kelompokkan per jenis.</strong> Plastik PET dengan PET, kertas dengan kertas — proses timbang di bank sampah jadi jauh lebih cepat.</li>
</ol>

<h2>Sampah berbahaya (B3) jangan dicampur</h2>
<p>Baterai bekas, lampu neon, aki, sisa obat, dan kaleng cat termasuk limbah B3. Jangan dicampur ke wadah mana pun — pisahkan dan serahkan ke titik pengumpulan khusus.</p>

<h2>Dasar hukumnya</h2>
<p>Kewajiban memilah bukan sekadar imbauan. <strong>Peraturan Gubernur Bali Nomor 47 Tahun 2019</strong> mewajibkan pengelolaan sampah berbasis sumber, termasuk pemilahan dan pengolahan organik di tingkat desa. Di tingkat nasional, dasarnya adalah <strong>Undang-Undang Nomor 18 Tahun 2008</strong> tentang Pengelolaan Sampah.</p>

<div class="ref">
<h3>Rujukan</h3>
<ol>
  <li>Posmaningsih, D.A.A., dkk. (2024). <em>Pengelolaan Bank Sampah, Identifikasi Permasalahan dan Solusinya</em>. Jurnal Pengabdian dan Pengembangan Masyarakat Indonesia, 3(2), 199–206. <a href="https://journalmpci.com/index.php/jppmi/article/download/300/186" target="_blank" rel="noopener">Tautan</a></li>
  <li>Peraturan Gubernur Bali Nomor 47 Tahun 2019 tentang Pengelolaan Sampah Berbasis Sumber.</li>
  <li>Undang-Undang Republik Indonesia Nomor 18 Tahun 2008 tentang Pengelolaan Sampah.</li>
</ol>
</div>
HTML,
            ],

            [
                'tipe'  => 'panduan',
                'judul' => 'Mengenal PSBS PADAS: Kelola Sampah dari Sumbernya',
                'thumb' => 'articles/psbs-padas.jpg',
                'hari'  => 95,
                'konten' => <<<'HTML'
<p><strong>PSBS PADAS</strong> — Pengelolaan Sampah Berbasis Sumber, Palemahan Kedas — adalah program Pemerintah Provinsi Bali yang menggeser cara kita memandang sampah: dari "kumpul–angkut–buang" menjadi "pilah–olah–tuntaskan di sumbernya".</p>

<h2>Apa yang berubah?</h2>
<p>Pada pola lama, sampah rumah tangga dicampur, diangkut, lalu ditumpuk di TPA. Pola ini rapuh: begitu TPA penuh, seluruh rantai berhenti. Pada PSBS, sampah diselesaikan sedekat mungkin dengan asalnya — organik dikompos di desa, anorganik masuk bank sampah, hanya residu yang diangkut.</p>

<h2>Kenapa mendesak di Bali?</h2>
<p>Timbulan sampah Bali mencapai sekitar <strong>1,2 juta ton pada 2024</strong>. Menurut Direktur Eksekutif IESR Fabby Tumiwa, volume sampah di Bali <strong>meningkat 30% sepanjang 2000–2024</strong>, didorong pertumbuhan wisatawan, konsumsi plastik sekali pakai, dan rendahnya kebiasaan memilah. Kenaikan itu tidak diimbangi kapasitas pengolahan.</p>
<p>Dalam <strong>Peta Jalan Kerthi Ekonomi Bali 2045</strong>, target yang dicanangkan adalah <strong>100% sampah terkelola</strong>.</p>

<h2>Peran desa dan desa adat</h2>
<p>Di Kabupaten Badung, penguatan dilakukan lewat dua jalur sekaligus. Jalur pemerintahan: desa dan kelurahan didorong mengaktifkan bank sampah dan TPS3R. Jalur adat: penerapan <strong>pararem</strong> — aturan desa adat — untuk menegakkan komitmen pemilahan, lengkap dengan sanksi adat bagi yang melanggar.</p>
<p>Pendekatan ini penting karena, seperti disimpulkan kajian kebijakan menjelang penutupan TPA Suwung, persoalan utama pengelolaan sampah di Bali <em>bukan pada peraturannya</em>, melainkan pada kepatuhan sosial, konsistensi penegakan, dan kesiapan infrastruktur hilir.</p>

<h2>Apa peran warga?</h2>
<ul>
  <li>Memilah sejak di rumah — organik, anorganik, residu.</li>
  <li>Menyetor anorganik ke bank sampah banjar, bukan membuangnya.</li>
  <li>Mengompos organik atau menyerahkannya ke TPS3R desa.</li>
  <li>Melaporkan pembuangan liar lewat aplikasi agar cepat ditangani.</li>
</ul>

<div class="ref">
<h3>Rujukan</h3>
<ol>
  <li>Pemerintah Provinsi Bali. <em>Program Pengelolaan Sampah Berbasis Sumber (PSBS) Palemahan Kedas</em>. <a href="https://www.baliprov.go.id/web/kuta-utara-dan-mengwi-dukung-langkah-duta-psbs-padas-percepat-pengelolaan-sampah-di-badung/" target="_blank" rel="noopener">Tautan</a></li>
  <li>Kompas.com (11 Februari 2025). <em>Bali Darurat Sampah, Ekonomi Sirkular Jadi Solusi?</em> <a href="https://money.kompas.com/read/2025/02/11/163000126/bali-darurat-sampah-ekonomi-sirkular-jadi-solusi-" target="_blank" rel="noopener">Tautan</a></li>
  <li>Sistem Informasi Wilayah dan Tata Ruang Bali (2025). <em>Krisis Pengelolaan Sampah di Bali Menjelang Penutupan TPA Suwung</em>. <a href="https://tarubali.baliprov.go.id/krisis-pengelolaan-sampah-di-bali-menjelang-penutupan-tpa-suwung-23-desember-2025-analisis-regulasi-risiko-praktik-internasional-dan-refleksi-kolektif-menuju-sistem-ekonomi-sirkular/" target="_blank" rel="noopener">Tautan</a></li>
</ol>
</div>
HTML,
            ],

            [
                'tipe'  => 'panduan',
                'judul' => 'Apa Itu TPS3R dan Bagaimana Cara Kerjanya',
                'thumb' => 'articles/tps3r.jpg',
                'hari'  => 70,
                'konten' => <<<'HTML'
<p><strong>TPS3R</strong> adalah Tempat Pengolahan Sampah dengan prinsip <em>Reduce, Reuse, Recycle</em>. Berbeda dari TPS biasa yang hanya menampung sementara, TPS3R <strong>mengolah</strong> — sehingga yang dikirim ke TPA hanya residunya.</p>

<h2>Alur kerjanya</h2>
<ol>
  <li><strong>Sampah masuk</strong> dari rumah tangga pelanggan, idealnya sudah terpilah.</li>
  <li><strong>Pemilahan lanjutan</strong> — organik, anorganik bernilai jual, dan residu dipisahkan.</li>
  <li><strong>Pengolahan organik</strong> menjadi kompos, atau pakan maggot Black Soldier Fly (BSF).</li>
  <li><strong>Anorganik</strong> dijual ke pengepul/industri daur ulang.</li>
  <li><strong>Residu</strong> — bagian yang benar-benar tidak terolah — baru diangkut ke TPA.</li>
</ol>

<h2>TPS3R di Kabupaten Badung</h2>
<p>Beberapa TPS3R yang beroperasi di Badung antara lain <strong>TPS3R Pecatu</strong> (Kuta Selatan), <strong>TPS3R Panca Lestari</strong> di Tanjung Benoa, <strong>TPS3R Kedonganan</strong> (Kuta), serta <strong>TPS3R Abirupa Pertiwi</strong> di Desa Bongkasa Pertiwi dan <strong>TPS3R Pudak Mesari</strong> di Desa Darmasaba (Abiansemal).</p>

<h2>Tantangan yang nyata: kapasitas</h2>
<p>Pada pemantauan Februari 2025, Bupati Badung mencatat kondisi di TPS3R Pecatu: sampah yang masuk mencapai <strong>±30 ton per hari</strong>, sementara kapasitas pengolahan hanya <strong>5–7 ton per hari</strong>. Selisih inilah yang berakhir sebagai beban TPA.</p>
<p>Angka itu menjelaskan satu hal penting: <em>membangun TPS3R saja tidak cukup</em>. Selama sampah datang dalam kondisi tercampur dan volumenya tak ditekan dari rumah, fasilitas sebagus apa pun akan kewalahan. Pemilahan di sumber bukan pelengkap — ia penentu.</p>

<h2>Kabar baiknya</h2>
<p>Di TPS3R Pecatu, hasil olahan sampah organik sudah berhasil diubah menjadi pupuk yang dipasarkan BUMDes — bukti bahwa sampah yang terkelola benar bisa berbalik menjadi pemasukan desa.</p>

<div class="ref">
<h3>Rujukan</h3>
<ol>
  <li>Pemerintah Kabupaten Badung (2025). <em>Bupati Adi Arnawa Tinjau 3 Lokasi TPS3R di Wilayah Kuta Selatan dan Kuta</em>. <a href="https://badungkab.go.id/index.php/kab/berita/62019-genjot-inovasi-pengelolaan-sampah-dan-siapkan-strategi-akselerasi-pengelolaan-sampah-bupati-adi-arnawa-tinjau-3-lokasi-tps3r-di-wilayah-kuta-selatan" target="_blank" rel="noopener">Tautan</a></li>
  <li>Prokompim Kabupaten Badung (2026). <em>Bupati Badung Bersama Gubernur Bali Dampingi Menteri LH Tinjau TPS3R di Badung</em>. <a href="https://prokompim.badungkab.go.id/berita/bupati-badung-bersama-gubernur-bali-dampingi-menteri-lh-tinjau-tps3r-di-badung.html" target="_blank" rel="noopener">Tautan</a></li>
</ol>
</div>
HTML,
            ],

            /* ---------------- TUTORIAL ---------------- */
            [
                'tipe'  => 'tutorial',
                'judul' => 'Panduan Setor Sampah lewat Aplikasi Niti Resik',
                'thumb' => 'articles/setor-sampah.jpg',
                'hari'  => 45,
                'konten' => <<<'HTML'
<p>Menyetor sampah di Niti Resik hanya butuh satu hal: <strong>QR ID</strong> yang ada di aplikasi Anda. Berikut alurnya dari rumah sampai saldo masuk.</p>

<h2>1. Pilah dan siapkan di rumah</h2>
<p>Pisahkan anorganik bernilai jual: botol PET, kardus, kertas, kaleng, botol kaca. Bilas, keringkan, pipihkan. Kelompokkan per jenis agar penimbangan cepat.</p>

<h2>2. Tidak yakin jenisnya? Pakai Klasifikasi AI</h2>
<p>Buka menu <strong>Scan AI</strong>, foto sampahnya. Sistem akan mengenali jenis dan kategorinya (organik / anorganik / B3), lengkap dengan langkah pengolahan yang disarankan.</p>

<h2>3. Cari bank sampah terdekat</h2>
<p>Buka menu <strong>Peta</strong> untuk melihat sebaran bank sampah dan TPS di sekitar Anda, beserta alamat dan jam layanannya.</p>

<h2>4. Tunjukkan QR ID Anda</h2>
<p>Di bank sampah, buka menu <strong>QR ID</strong>. Petugas memindainya, menimbang setoran, dan memasukkan rinciannya per jenis sampah.</p>

<h2>5. Saldo masuk otomatis</h2>
<p>Begitu petugas menyimpan, saldo langsung terkredit ke dompet digital Anda — tidak ada pencatatan manual, tidak ada buku tabungan yang bisa hilang. Anda menerima notifikasi, dan seluruh riwayat tersimpan di menu <strong>Saldo</strong>.</p>

<h2>6. Saldonya mau diapakan?</h2>
<ul>
  <li><strong>Belanja produk daur ulang</strong> dari UMKM di marketplace Niti Resik.</li>
  <li><strong>Bayar iuran TPS</strong> langganan bulanan Anda.</li>
  <li><strong>Tarik ke rekening bank</strong> bila saldo sudah mencukupi batas minimum.</li>
</ul>

<h2>Tips agar nilainya maksimal</h2>
<p>Kumpulkan dulu sampai jumlah tertentu sebelum menyetor — makin banyak, makin terasa nilainya. Jangan campur jenis yang berbeda harga (misalnya PET bening dengan PET warna), karena bisa terhitung dengan harga terendah.</p>
HTML,
            ],

            /* ---------------- VIDEO ---------------- */
            [
                'tipe'  => 'tutorial',
                'judul' => 'Video: Cara Mudah Mengelola Sampah Rumah Tangga',
                'thumb' => 'articles/video-kelola-sampah.jpg',
                'hari'  => 60,
                'konten' => <<<'HTML'
<p>Video singkat ini menunjukkan praktik mengelola sampah rumah tangga sehari-hari — dari memilah sampai memanfaatkan kembali. Cocok ditonton sekeluarga sebelum mulai memilah di rumah.</p>

<div class="video-embed">
  <iframe width="560" height="315" src="https://www.youtube.com/embed/xugKfENVhiM"
          title="Cara Mudah Mengelola Sampah Rumah Tangga"
          frameborder="0" allowfullscreen loading="lazy"></iframe>
</div>

<h2>Poin penting dari video</h2>
<ul>
  <li>Memilah dimulai dari menyediakan wadah terpisah — tidak perlu mahal.</li>
  <li>Organik dapat langsung diolah jadi kompos di pekarangan.</li>
  <li>Anorganik yang bersih dan kering punya nilai jual di bank sampah.</li>
</ul>

<h2>Setelah menonton</h2>
<p>Coba mulai dari satu hal saja: sediakan satu wadah khusus botol plastik di dapur minggu ini. Ketika sudah terkumpul, setorkan ke bank sampah banjar terdekat lewat menu Peta di aplikasi.</p>

<div class="ref">
<h3>Sumber video</h3>
<p><a href="https://www.youtube.com/watch?v=xugKfENVhiM" target="_blank" rel="noopener">Cara Mudah Mengelola Sampah Rumah Tangga — YouTube</a></p>
</div>
HTML,
            ],

            [
                'tipe'  => 'tutorial',
                'judul' => 'Video: Rumah Edukasi Pilah Sampah — Ketika Memilah Jadi Gerakan Warga',
                'thumb' => 'articles/video-rumah-edukasi.jpg',
                'hari'  => 30,
                'konten' => <<<'HTML'
<p>Liputan Metro TV tentang Rumah Edukasi Komunitas Pilah Sampah yang mengajak masyarakat lebih peduli memilah sampah rumah tangga. Contoh nyata bahwa gerakan warga bisa berdampak tanpa menunggu perintah.</p>

<div class="video-embed">
  <iframe width="560" height="315" src="https://www.youtube.com/embed/QRGzADNJGDY"
          title="Rumah Edukasi Pilah Sampah Ajak Masyarakat Mengurangi Sampah"
          frameborder="0" allowfullscreen loading="lazy"></iframe>
</div>

<h2>Apa yang bisa ditiru di banjar kita?</h2>
<ul>
  <li>Titik pilah bersama di balai banjar — memudahkan warga yang rumahnya sempit.</li>
  <li>Edukasi rutin lewat kegiatan PKK dan sekaa teruna.</li>
  <li>Pencatatan digital agar setoran warga transparan dan terlacak.</li>
</ul>

<div class="ref">
<h3>Sumber video</h3>
<p><a href="https://www.youtube.com/watch?v=QRGzADNJGDY" target="_blank" rel="noopener">Metro Community — Rumah Edukasi Pilah Sampah Ajak Masyarakat Mengurangi Sampah</a></p>
</div>
HTML,
            ],

            /* ---------------- ARTIKEL ---------------- */
            [
                'tipe'  => 'artikel',
                'judul' => 'Manfaat Bank Sampah bagi Krama Banjar: Bukan Sekadar Uang Receh',
                'thumb' => 'articles/bank-sampah.jpg',
                'hari'  => 80,
                'konten' => <<<'HTML'
<p>Bank sampah sering diremehkan karena nilainya terlihat kecil per setoran. Padahal kajian akademik menunjukkan dampaknya jauh melampaui angka di buku tabungan.</p>

<h2>Tiga manfaat yang terukur</h2>
<h3>1. Ekonomi</h3>
<p>Penelitian Gunartin dkk. (2020) di Bank Sampah Ketumbar Pamulang menemukan bank sampah berperan nyata meningkatkan <em>ekonomi kreatif</em> masyarakat — bukan hanya dari penjualan sampah, tetapi dari produk turunan yang dihasilkan warga. Studi Miftahorrozi dkk. (2022) di <em>Journal of Risk and Financial Management</em> juga menunjukkan keterkaitan bank sampah dengan pemberdayaan sosial-ekonomi di Indonesia.</p>

<h3>2. Lingkungan</h3>
<p>Setiap kilogram anorganik yang disetor adalah kilogram yang tidak berakhir di TPA. Dengan kondisi Bali yang hanya mampu menangani sekitar 48% dari 4.281 ton sampah hariannya, pengalihan sekecil apa pun berarti.</p>

<h3>3. Sosial</h3>
<p>Posmaningsih dkk. (2024) menegaskan bahwa <strong>kunci keberhasilan bank sampah terletak pada partisipasi warga sekitar</strong> — bukan pada peralatan atau bangunannya. Bank sampah menjadi ruang temu warga, tempat kebiasaan baru ditumbuhkan bersama.</p>

<h2>Kenapa banyak bank sampah mandek?</h2>
<p>Studi yang sama menemukan persoalan bank sampah tidak berhenti pada aspek teknis, melainkan menyangkut beberapa aspek lain yang saling berkaitan — mulai dari manajemen, pencatatan, hingga konsistensi partisipasi. Bank sampah yang bergantung pada satu-dua orang penggerak akan berhenti begitu orang itu berhalangan.</p>
<p>Di sinilah pencatatan digital berperan: ketika setoran, saldo, dan riwayat tersimpan otomatis, bank sampah tidak lagi bergantung pada ingatan atau buku tulis seseorang.</p>

<h2>Bank sampah di Kabupaten Badung</h2>
<p>Badung mengembangkan jaringan <strong>Bank Sampah Mandiri (BSM) PKK "Mangu Srikandi"</strong> di tingkat desa/kelurahan, serta <strong>Bank Sampah Edukasi Badung (BSEB) "Mangu Kumara"</strong> yang menyasar sekolah — keduanya bagian dari gerakan Badung bersih sampah.</p>

<div class="ref">
<h3>Rujukan</h3>
<ol>
  <li>Gunartin, G., Mulyanto, E., &amp; Sunarsi, D. (2020). <em>The Role Analysis of Waste Bank in Improving the Community's Creative Economy (Study at Ketumbar Pamulang Waste Bank)</em>. BIRCI-Journal: Humanities and Social Sciences, 3(4), 3262–3269. <a href="https://doi.org/10.33258/birci.v3i4.1360" target="_blank" rel="noopener">https://doi.org/10.33258/birci.v3i4.1360</a></li>
  <li>Miftahorrozi, M., Khan, S., &amp; Bhatti, M.I. (2022). <em>Waste Bank–Socio-Economic Empowerment Nexus in Indonesia: The Stance of Maqasid al-Shari'ah</em>. Journal of Risk and Financial Management, 15(7).</li>
  <li>Posmaningsih, D.A.A., dkk. (2024). <em>Pengelolaan Bank Sampah, Identifikasi Permasalahan dan Solusinya</em>. Jurnal Pengabdian dan Pengembangan Masyarakat Indonesia, 3(2), 199–206.</li>
</ol>
</div>
HTML,
            ],

            [
                'tipe'  => 'artikel',
                'judul' => 'Mengurangi Beban TPA Suwung Dimulai dari Dapur Rumah',
                'thumb' => 'articles/tpa-suwung.jpg',
                'hari'  => 55,
                'konten' => <<<'HTML'
<p>Selama puluhan tahun, sampah Bali Selatan bermuara di satu titik: TPA Suwung. Model ini kini sampai pada batasnya, dan jalan keluarnya justru ada di tempat yang paling dekat — dapur kita.</p>

<h2>Masalahnya bukan aturan</h2>
<p>Kajian kebijakan menjelang penutupan TPA Suwung menyimpulkan sesuatu yang penting: masalah utama <strong>tidak terletak pada peraturan</strong>, melainkan pada kepatuhan sosial, konsistensi penegakan, dan kesiapan infrastruktur hilir. Aturannya sudah ada sejak lama — yang belum jalan adalah kebiasaannya.</p>

<h2>Organik: separuh masalah, separuh solusi</h2>
<p>Data KLHK menunjukkan sampah organik sisa makanan menyumbang porsi terbesar timbulan sampah nasional, dan <strong>sekitar 38,2% sampah berasal dari rumah tangga</strong>. Sampah organik yang menumpuk di TPA juga menghasilkan gas metana — penyumbang besar emisi gas rumah kaca.</p>
<p>Perhitungan KLHK memberi gambaran skala dampaknya: bila seluruh masyarakat Indonesia mengompos sampah sisa makanan secara mandiri di rumah, sekitar <strong>10,92 juta ton sampah organik tidak terbawa ke TPA</strong>, dan emisi dapat ditekan sekitar <strong>6,8 juta ton CO₂ ekuivalen</strong>.</p>

<h2>Yang bisa dilakukan mulai minggu ini</h2>
<ol>
  <li><strong>Kompos sisa dapur.</strong> Ember komposter sederhana sudah cukup untuk keluarga kecil.</li>
  <li><strong>Setor anorganik ke bank sampah.</strong> Botol, kardus, kaleng — semuanya masih bernilai.</li>
  <li><strong>Tekan residu.</strong> Kurangi kemasan sekali pakai dan multilayer yang tidak dapat didaur ulang.</li>
  <li><strong>Laporkan pembuangan liar.</strong> Satu laporan mencegah satu titik jadi TPA dadakan.</li>
</ol>

<h2>Arah kebijakan Bali</h2>
<p>Menteri Lingkungan Hidup menginstruksikan agar pengelola swakelola tidak lagi mengirim sampah ke TPA dalam kondisi tercampur, dan mendorong agar praktik baik pengelolaan sampah berbasis desa — seperti di Desa Bongkasa Pertiwi dan Darmasaba, Badung — disusun sistematis agar dapat direplikasi ke seluruh desa.</p>

<div class="ref">
<h3>Rujukan</h3>
<ol>
  <li>Sistem Informasi Wilayah dan Tata Ruang Bali (2025). <em>Krisis Pengelolaan Sampah di Bali Menjelang Penutupan TPA Suwung 23 Desember 2025</em>. <a href="https://tarubali.baliprov.go.id/krisis-pengelolaan-sampah-di-bali-menjelang-penutupan-tpa-suwung-23-desember-2025-analisis-regulasi-risiko-praktik-internasional-dan-refleksi-kolektif-menuju-sistem-ekonomi-sirkular/" target="_blank" rel="noopener">Tautan</a></li>
  <li>KLHK. <em>Gerakan Nasional Compost Day Satu Negeri</em>, Dashboard Pengurangan Sampah. <a href="https://info3r.menlhk.go.id/berita/detail/berita-16-v_berita" target="_blank" rel="noopener">Tautan</a></li>
  <li>Prokompim Kabupaten Badung (2026). <em>Bupati Badung Bersama Gubernur Bali Dampingi Menteri LH Tinjau TPS3R di Badung</em>.</li>
</ol>
</div>
HTML,
            ],

            [
                'tipe'  => 'artikel',
                'judul' => 'Sad Kerthi dan Tanggung Jawab Menjaga Palemahan',
                'thumb' => 'articles/sad-kerthi.jpg',
                'hari'  => 100,
                'konten' => <<<'HTML'
<p>Jauh sebelum istilah <em>ekonomi sirkular</em> dikenal, Bali sudah punya kerangkanya sendiri. Sad Kerthi — enam upaya penyucian — menempatkan manusia bukan sebagai penguasa alam, melainkan bagian darinya.</p>

<h2>Enam Kerthi, satu keseimbangan</h2>
<ul>
  <li><strong>Atma Kerthi</strong> — penyucian jiwa, lewat kesadaran dan edukasi.</li>
  <li><strong>Wana Kerthi</strong> — kelestarian hutan dan tumbuhan.</li>
  <li><strong>Danu Kerthi</strong> — kelestarian sumber air.</li>
  <li><strong>Segara Kerthi</strong> — kelestarian laut dan pantai.</li>
  <li><strong>Jagat Kerthi</strong> — keharmonisan alam semesta.</li>
  <li><strong>Jana Kerthi</strong> — kesejahteraan manusia.</li>
</ul>

<h2>Di mana sampah dalam kerangka ini?</h2>
<p>Sampah menyentuh hampir semuanya sekaligus. Sampah yang dibakar mencemari udara (<em>Jagat Kerthi</em>). Sampah yang masuk sungai merusak sumber air (<em>Danu Kerthi</em>) dan berakhir di laut (<em>Segara Kerthi</em>). Sampah yang dibiarkan menumpuk menurunkan kualitas hidup warga (<em>Jana Kerthi</em>).</p>
<p>Dalam Tri Hita Karana, urusan ini masuk ranah <strong>Palemahan</strong> — hubungan harmonis manusia dengan alam. Memilah sampah, dengan demikian, bukan sekadar tertib administrasi. Ia laku menjaga keseimbangan.</p>

<h2>Nilai lama, tantangan baru</h2>
<p>Yang berubah bukan nilainya, melainkan skalanya. Sampah organik zaman dulu terurai sendiri di teba. Hari ini, plastik sekali pakai tidak. Karena itu nilai lama perlu wujud baru: bank sampah, TPS3R, pencatatan digital, dan pararem desa adat yang menegakkan komitmen pemilahan.</p>
<p>Pemerintah Kabupaten Badung menempuh jalan ini — mengolaborasikan pemerintah desa dengan desa adat lewat penerapan pararem, termasuk sanksi adat bagi warga yang melanggar komitmen pemilahan.</p>

<h2>Menutup lingkaran</h2>
<p>Ekonomi sirkular sesungguhnya adalah bahasa modern dari gagasan lama: tidak ada yang benar-benar terbuang, semuanya kembali. Ketika sampah dapur menjadi kompos yang menyuburkan tanah, dan botol bekas menjadi kerajinan yang menghidupi keluarga — di situlah Sad Kerthi bekerja, bukan sebagai wacana, melainkan sebagai kebiasaan.</p>

<div class="ref">
<h3>Rujukan</h3>
<ol>
  <li>Prokompim Kabupaten Badung (2026). <em>Bupati Badung Bersama Gubernur Bali Dampingi Menteri LH Tinjau TPS3R di Badung</em> — penerapan pararem desa adat untuk komitmen pemilahan sampah.</li>
  <li>Pemerintah Provinsi Bali. <em>Peta Jalan Kerthi Ekonomi Bali 2045</em>.</li>
</ol>
</div>
HTML,
            ],

            /* ---------------- JURNAL ---------------- */
            [
                'tipe'  => 'jurnal',
                'judul' => 'Jurnal: Pengelolaan Bank Sampah — Identifikasi Permasalahan dan Solusinya',
                'thumb' => 'articles/jurnal-bank-sampah.jpg',
                'hari'  => 110,
                'konten' => <<<'HTML'
<p class="meta"><strong>Posmaningsih, D.A.A., dkk. (2024)</strong> · Jurnal Pengabdian dan Pengembangan Masyarakat Indonesia, Vol. 3 No. 2, hlm. 199–206.</p>

<h2>Ringkasan</h2>
<p>Studi ini menelaah pengelolaan bank sampah di Desa Marga, Bali, dan memetakan persoalan yang menghambatnya. Temuan utamanya: hambatan bank sampah <strong>tidak berhenti pada aspek teknis</strong>, melainkan mencakup enam aspek lain yang saling berkaitan. Penulis menyimpulkan bahwa <strong>kunci keberhasilan bank sampah terletak pada partisipasi aktif warga sekitar</strong>.</p>

<h2>Konteks data</h2>
<p>Penelitian ini mencatat bahwa dari sekitar <strong>4.281 ton sampah per hari</strong> yang dihasilkan Bali, hanya sekitar <strong>48% yang tertangani dengan baik</strong>; sisanya dibuang sembarangan, dibakar, atau dibuang ke sungai. Komposisi sampah didominasi organik (60%), anorganik (30%), dan residu (10%).</p>

<h2>Kaitannya dengan Niti Resik</h2>
<p>Temuan bahwa partisipasi warga — bukan peralatan — yang menentukan keberhasilan menjadi dasar rancangan Niti Resik: sistem ini menempatkan warga sebagai titik awal, dengan insentif langsung (saldo) dan pencatatan otomatis agar pengelolaan tidak bergantung pada satu-dua penggerak.</p>

<div class="ref">
<h3>Akses jurnal</h3>
<p><a href="https://journalmpci.com/index.php/jppmi/article/download/300/186" target="_blank" rel="noopener">Unduh naskah lengkap (PDF)</a></p>
</div>
HTML,
            ],

            [
                'tipe'  => 'jurnal',
                'judul' => 'Jurnal: Peran Bank Sampah dalam Meningkatkan Ekonomi Kreatif Masyarakat',
                'thumb' => 'articles/jurnal-ekonomi-kreatif.jpg',
                'hari'  => 105,
                'konten' => <<<'HTML'
<p class="meta"><strong>Gunartin, G., Mulyanto, E., &amp; Sunarsi, D. (2020)</strong> · Budapest International Research and Critics Institute (BIRCI-Journal): Humanities and Social Sciences, 3(4), hlm. 3262–3269.</p>

<h2>Ringkasan</h2>
<p>Studi kasus pada Bank Sampah Ketumbar, Pamulang, yang menganalisis peran bank sampah dalam meningkatkan ekonomi kreatif masyarakat. Penelitian ini menunjukkan bank sampah tidak berhenti sebagai tempat jual-beli sampah, melainkan menjadi pemantik kegiatan ekonomi kreatif warga melalui produk daur ulang.</p>

<h2>Kaitannya dengan Niti Resik</h2>
<p>Temuan ini mendasari keputusan menghubungkan bank sampah dengan <strong>marketplace UMKM</strong> di dalam satu sistem. Sampah yang disetor warga tidak berhenti sebagai komoditas mentah — ia menjadi bahan baku UMKM daur ulang, yang produknya kembali dibeli warga memakai saldo hasil setoran. Lingkarannya tertutup.</p>

<div class="ref">
<h3>Akses jurnal</h3>
<p>DOI: <a href="https://doi.org/10.33258/birci.v3i4.1360" target="_blank" rel="noopener">10.33258/birci.v3i4.1360</a></p>
</div>
HTML,
            ],

            [
                'tipe'  => 'jurnal',
                'judul' => 'Jurnal: Bank Sampah dan Pemberdayaan Sosial-Ekonomi di Indonesia',
                'thumb' => 'articles/jurnal-pemberdayaan.jpg',
                'hari'  => 90,
                'konten' => <<<'HTML'
<p class="meta"><strong>Miftahorrozi, M., Khan, S., &amp; Bhatti, M.I. (2022)</strong> · Journal of Risk and Financial Management, 15(7). Penerbit: MDPI.</p>

<h2>Ringkasan</h2>
<p>Kajian ini menelaah hubungan antara bank sampah dan pemberdayaan sosial-ekonomi masyarakat di Indonesia, ditinjau dari kerangka <em>Maqasid al-Shari'ah</em>. Studi ini menempatkan bank sampah bukan semata instrumen lingkungan, melainkan mekanisme pemberdayaan ekonomi warga.</p>

<h2>Kaitannya dengan Niti Resik</h2>
<p>Kajian ini memperkuat argumen bahwa nilai sosial-ekonomi bank sampah perlu dibuat <strong>terlihat dan terukur</strong> bagi warga. Karena itu Niti Resik menampilkan saldo, riwayat setoran, dan total berat sampah yang dialihkan — agar manfaatnya terasa nyata, bukan abstrak.</p>

<div class="ref">
<h3>Akses jurnal</h3>
<p>Journal of Risk and Financial Management, Vol. 15, No. 7 (2022) — akses terbuka melalui MDPI.</p>
</div>
HTML,
            ],

            [
                'tipe'  => 'jurnal',
                'judul' => 'Jurnal: Strategi Penguatan Bank Sampah dalam Implementasi Zero Waste',
                'thumb' => 'articles/jurnal-zero-waste.jpg',
                'hari'  => 20,
                'konten' => <<<'HTML'
<p class="meta"><strong>Sudiyanto, I.W., &amp; HS, S.M. (2025)</strong> · Jutin: Jurnal Teknik Industri Terintegrasi, 8(3), hlm. 2856–2865. Terindeks DOAJ, lisensi CC BY-SA.</p>

<h2>Ringkasan</h2>
<p>Studi literatur mengenai strategi penguatan bank sampah dalam mewujudkan <em>zero waste</em> di Indonesia. Penelitian ini menempatkan bank sampah dalam kerangka ekonomi sirkular — mengubah sampah menjadi produk bernilai dan dapat dimanfaatkan kembali.</p>

<h2>Konteks data</h2>
<p>Kajian ini mencatat Indonesia menghasilkan sekitar <strong>68 juta ton sampah setiap tahun</strong>, dengan sekitar <strong>60% berasal dari kawasan perkotaan</strong>. Pertumbuhan penduduk, urbanisasi, dan gaya hidup konsumtif menjadi pendorong utamanya.</p>

<h2>Kaitannya dengan Niti Resik</h2>
<p>Penguatan yang disarankan dalam kajian ini — kelembagaan, pencatatan, dan keberlanjutan partisipasi — menjadi alasan Niti Resik mendigitalkan proses bank sampah dan menghubungkannya langsung dengan pemerintah daerah, agar penguatan tidak berhenti pada pelatihan sesaat.</p>

<div class="ref">
<h3>Akses jurnal</h3>
<p><a href="https://journal.universitaspahlawan.ac.id/index.php/jutin/article/download/47008/29733/163845" target="_blank" rel="noopener">Unduh naskah lengkap (PDF)</a></p>
</div>
HTML,
            ],
        ];

        foreach ($artikel as $a) {
            Article::create([
                'author_id'    => $penulis?->id,
                'tipe'         => $a['tipe'],
                'judul'        => $a['judul'],
                'slug'         => Str::slug($a['judul']),
                'konten'       => $a['konten'],
                'thumbnail'    => $a['thumb'] ?? null,
                'status'       => 'published',
                'published_at' => now()->subDays($a['hari']),
            ]);
        }
    }
}