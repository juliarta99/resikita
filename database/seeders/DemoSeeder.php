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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

class DemoSeeder extends Seeder
{
    private $faker;
    private int $seq = 0;
    private array $saldo = [];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->faker = \Faker\Factory::create('id_ID');

        $this->command->info('› Wilayah & pejabat...');
        [$banjars] = $this->seedWilayah();

        $this->command->info('› Eksekutif & admin...');
        $this->seedEksekutif();

        $this->command->info('› Harga sampah & kategori...');
        $wastePrices = $this->seedMasterData();

        $this->command->info('› Masyarakat & petugas lapangan...');
        [$masyarakat, $petugasLapangan] = $this->seedWarga($banjars);

        $this->command->info('› Bank sampah & petugas...');
        [$bankSampahs, $petugasBS] = $this->seedBankSampah($banjars);

        $this->command->info('› TPS & langganan...');
        $this->seedTps($banjars, $masyarakat);

        $this->command->info('› UMKM & produk...');
        [$umkms, $products] = $this->seedUmkm($banjars);

        $this->command->info('› Setor sampah (kredit saldo)...');
        $this->seedDeposits($bankSampahs, $petugasBS, $masyarakat, $wastePrices);

        $this->command->info('› Penarikan saldo...');
        $this->seedWithdrawals($masyarakat);

        $this->command->info('› Pesanan marketplace...');
        $this->seedOrders($masyarakat, $umkms, $products);

        $this->command->info('› Laporan sampah...');
        $this->seedReports($masyarakat, $petugasLapangan, $banjars);

        $this->command->info('› Klasifikasi AI, artikel, notifikasi...');
        $this->seedMisc($masyarakat);

        $this->command->info('✔ Seeder demo selesai.');
    }

    /* ============================================================ */

    private function makeUser(array $attrs, string $role, array $scope = []): User
    {
        $this->seq++;

        $user = User::create(array_merge([
            'nik'               => '5103' . str_pad((string) $this->seq, 12, '0', STR_PAD_LEFT),
            'tanggal_lahir'     => $this->faker->dateTimeBetween('-55 years', '-18 years')->format('Y-m-d'),
            'jenis_kelamin'     => $this->faker->randomElement(['L', 'P']),
            'phone'             => '628' . str_pad((string) $this->seq, 9, '0', STR_PAD_LEFT),
            'phone_verified_at' => now(),
            'password'          => Hash::make('password'),
            'is_active'         => true,
        ], $attrs, $scope));

        $user->assignRole($role);

        return $user;
    }

    private function coord(): array
    {
        return [
            'lat' => $this->faker->randomFloat(7, -8.82, -8.50),
            'lng' => $this->faker->randomFloat(7, 115.08, 115.26),
        ];
    }

    private function wallet(User $user, float $delta, string $tipe, $ref = null, ?string $ket = null): void
    {
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id], ['saldo' => 0]);
        $current = $this->saldo[$user->id] ?? (float) $wallet->saldo;
        $new = $current + $delta;

        if ($new < 0) {
            return; // jaga saldo tidak minus saat seeding
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

    /* ============================================================ */

    private function seedWilayah(): array
    {
        $struktur = [
            'Kuta'         => ['Kuta', 'Legian', 'Seminyak'],
            'Kuta Selatan' => ['Jimbaran', 'Ungasan', 'Benoa'],
            'Kuta Utara'   => ['Kerobokan', 'Canggu', 'Tibubeneng'],
            'Mengwi'       => ['Sempidi', 'Lukluk', 'Sading'],
            'Abiansemal'   => ['Sedang', 'Blahkiuh', 'Mambal'],
            'Petang'       => ['Petang', 'Pelaga', 'Carangsari'],
        ];

        $banjars = [];

        foreach ($struktur as $namaKec => $kelurahanList) {
            $kec = Kecamatan::firstOrCreate(['nama' => $namaKec]);
            $this->makeUser([
                'name'  => 'Camat ' . $namaKec,
                'email' => 'camat.' . Str::slug($namaKec) . '@badung.go.id',
                'nip'   => '1976' . str_pad((string) $this->seq, 14, '0', STR_PAD_LEFT),
            ], 'camat', ['kecamatan_id' => $kec->id]);

            foreach ($kelurahanList as $namaKel) {
                $kel = Kelurahan::firstOrCreate(['kecamatan_id' => $kec->id, 'nama' => $namaKel]);
                $this->makeUser([
                    'name'  => 'Lurah ' . $namaKel,
                    'email' => 'lurah.' . Str::slug($namaKel) . '.' . $this->seq . '@badung.go.id',
                    'nip'   => '1980' . str_pad((string) $this->seq, 14, '0', STR_PAD_LEFT),
                ], 'lurah', ['kecamatan_id' => $kec->id, 'kelurahan_id' => $kel->id]);

                foreach (['Kaja', 'Kelod'] as $arah) {
                    $banjar = BanjarDinas::firstOrCreate([
                        'kelurahan_id' => $kel->id,
                        'nama'         => 'Banjar ' . $namaKel . ' ' . $arah,
                    ]);
                    $this->makeUser([
                        'name'  => 'Kepala Dinas ' . $banjar->nama,
                        'email' => 'kadis.' . Str::slug($banjar->nama) . '.' . $this->seq . '@badung.go.id',
                        'nip'   => '1985' . str_pad((string) $this->seq, 14, '0', STR_PAD_LEFT),
                    ], 'kepala_dinas_banjar', [
                        'kecamatan_id' => $kec->id,
                        'kelurahan_id' => $kel->id,
                        'banjar_id'    => $banjar->id,
                    ]);
                    $banjars[] = $banjar;
                }
            }
        }

        return [$banjars];
    }

    private function seedEksekutif(): void
    {
        $this->makeUser(['name' => 'Bupati Badung', 'email' => 'bupati@badung.go.id', 'nip' => '1970' . str_pad((string) $this->seq, 14, '0', STR_PAD_LEFT)], 'bupati');
        $this->makeUser(['name' => 'Admin Dinas LHK', 'email' => 'dinas@badung.go.id', 'nip' => '1982' . str_pad((string) $this->seq, 14, '0', STR_PAD_LEFT)], 'admin_dinas');
        $this->makeUser(['name' => 'Administrator', 'email' => 'admin@nitiresik.id'], 'admin');
    }

    private function seedMasterData(): array
    {
        $prices = [
            ['Botol Plastik PET', 4000], ['Kardus', 2500], ['Kertas HVS', 3000],
            ['Koran Bekas', 2000], ['Kaleng Aluminium', 12000], ['Besi', 5000],
            ['Botol Kaca', 800], ['Plastik Kresek', 1500], ['Tembaga', 60000],
            ['Minyak Jelantah', 6000],
        ];
        $out = [];
        foreach ($prices as [$nama, $harga]) {
            $out[] = WastePrice::firstOrCreate(
                ['jenis_sampah' => $nama],
                ['satuan' => 'kg', 'harga_per_kg' => $harga, 'is_active' => true]
            );
        }

        foreach (['Pupuk & Kompos', 'Kerajinan', 'Fesyen Daur Ulang', 'Rumah Tangga', 'Aksesori'] as $c) {
            ProductCategory::firstOrCreate(['nama' => $c]);
        }

        $rc = ['Pembakaran sampah', 'Pembuangan liar', 'Saluran tersumbat', 'Sampah di sungai/pantai', 'Sampah menumpuk', 'TPS penuh'];
        foreach ($rc as $c) {
            ReportCategory::firstOrCreate(['nama' => $c]);
        }

        return $out;
    }

    private function seedWarga(array $banjars): array
    {
        $masyarakat = [];
        for ($i = 0; $i < 30; $i++) {
            $b = $this->faker->randomElement($banjars);
            $u = $this->makeUser(array_merge([
                'name'              => $this->faker->name(),
                'email'             => 'warga' . ($i + 1) . '@mail.test',
                'nik'               => str_pad((string) (5100000000000000 + $i + 1), 16, '0', STR_PAD_LEFT),
                'phone'             => '628' . str_pad((string) (1100000000 + $i + 1), 11, '0', STR_PAD_LEFT),
                'phone_verified_at' => now(),
                'kode_qr'           => 'NR' . str_pad((string) ($this->seq + 1), 8, '0', STR_PAD_LEFT),
            ], $this->coord()), 'masyarakat', [
                'kecamatan_id' => $b->kelurahan->kecamatan_id,
                'kelurahan_id' => $b->kelurahan_id,
                'banjar_id'    => $b->id,
            ]);
            Wallet::create(['user_id' => $u->id, 'saldo' => 0]);
            $masyarakat[] = $u;
        }

        $petugasLapangan = [];
        for ($i = 0; $i < 5; $i++) {
            $petugasLapangan[] = $this->makeUser([
                'name'  => 'Petugas Lapangan ' . ($i + 1),
                'email' => 'lapangan' . ($i + 1) . '@nitiresik.id',
            ], 'petugas_lapangan');
        }

        return [$masyarakat, $petugasLapangan];
    }

    private function seedBankSampah(array $banjars): array
    {
        $names = ['Bank Sampah Sari Lestari', 'Bank Sampah Bumi Hijau', 'Bank Sampah Werdhi Guna', 'Bank Sampah Amerta'];
        $bankSampahs = [];
        $petugasBS = [];

        foreach ($names as $i => $nama) {
            $b = $this->faker->randomElement($banjars);
            $bs = BankSampah::create(array_merge([
                'nama'      => $nama,
                'alamat'    => $this->faker->address(),
                'no_hp'     => '0361' . $this->faker->numberBetween(700000, 999999),
                'banjar_id' => $b->id,
            ], $this->coord()));

            $this->makeUser([
                'name'  => 'Admin ' . $nama,
                'email' => 'adminbs' . ($i + 1) . '@nitiresik.id',
            ], 'admin_bank_sampah', ['bank_sampah_id' => $bs->id]);

            $ps = [];
            for ($p = 0; $p < 2; $p++) {
                $ps[] = $this->makeUser([
                    'name'  => 'Petugas ' . $nama . ' ' . ($p + 1),
                    'email' => 'petugasbs' . ($i + 1) . '_' . ($p + 1) . '@nitiresik.id',
                ], 'petugas_bank_sampah', ['bank_sampah_id' => $bs->id]);
            }

            $bankSampahs[] = $bs;
            $petugasBS[$bs->id] = $ps;
        }

        return [$bankSampahs, $petugasBS];
    }

    private function seedTps(array $banjars, array $masyarakat): void
    {
        $tpsList = [];
        for ($i = 0; $i < 5; $i++) {
            $b = $this->faker->randomElement($banjars);
            $berbayar = $this->faker->boolean(60);
            $tps = Tps::create(array_merge([
                'nama'        => 'TPS ' . $b->kelurahan->nama . ' ' . ($i + 1),
                'alamat'      => $this->faker->address(),
                'no_hp'       => '0361' . $this->faker->numberBetween(700000, 999999),
                'is_berbayar' => $berbayar,
                'tarif'       => $berbayar ? $this->faker->randomElement([15000, 20000, 25000]) : null,
                'banjar_id'   => $b->id,
            ], $this->coord()));

            $this->makeUser([
                'name'  => 'Admin ' . $tps->nama,
                'email' => 'admintps' . ($i + 1) . '@nitiresik.id',
            ], 'admin_tps', ['tps_id' => $tps->id]);

            $tpsList[] = $tps;
        }

        // Langganan: sebagian warga jadi anggota TPS berbayar
        $anggota = collect($masyarakat)->random(15);
        foreach ($anggota as $warga) {
            $tps = $this->faker->randomElement($tpsList);
            $member = TpsMember::firstOrCreate(
                ['tps_id' => $tps->id, 'user_id' => $warga->id],
                ['status' => 'aktif', 'joined_at' => now()->subDays($this->faker->numberBetween(30, 300))]
            );

            if (! $tps->is_berbayar) {
                continue;
            }

            foreach ([now()->subMonth(), now()] as $bulan) {
                $lunas = $this->faker->boolean(70);
                $metode = $this->faker->randomElement(['saldo', 'midtrans']);
                TpsSubscription::create([
                    'periode'      => $bulan->format('Y-m'),
                    'jumlah'       => $tps->tarif,
                    'status'       => $lunas ? 'lunas' : 'menunggu',
                    'metode_bayar' => $lunas ? $metode : null,
                    'paid_at'      => $lunas ? $bulan : null,
                    'tps_member_id' => $member->id,
                ]);

                if ($lunas && $metode === 'saldo') {
                    $this->wallet($warga, -$tps->tarif, 'belanja', $tps, 'Iuran TPS ' . $bulan->format('M Y'));
                }
            }
        }
    }

    private function seedUmkm(array $banjars): array
    {
        $categories = ProductCategory::pluck('id')->all();
        $umkmNames = [
            'Kompos Werdhi'      => ['Pupuk Kompos Organik 5kg', 'Pupuk Cair Bio 1L', 'Media Tanam Premium'],
            'Ecobrick Bali'      => ['Kursi Ecobrick', 'Pot Ecobrick', 'Meja Kecil Ecobrick'],
            'Rajut Sampah Craft' => ['Tas Rajut Plastik', 'Dompet Daur Ulang', 'Topi Anyaman'],
            'Loka Recycle'       => ['Tote Bag Kanvas Daur Ulang', 'Sandal Ban Bekas', 'Tempat Pensil'],
            'Griya Daur'         => ['Lampu Hias Botol', 'Vas Bunga Kaca', 'Gantungan Kunci'],
            'Sari Organik'       => ['Sabun Minyak Jelantah', 'Lilin Aromaterapi', 'Cairan Pembersih Eco'],
        ];

        $umkms = [];
        $products = [];
        $i = 0;

        foreach ($umkmNames as $nama => $produkList) {
            $i++;
            $b = $this->faker->randomElement($banjars);
            $umkm = Umkm::create(array_merge([
                'nama'      => $nama,
                'deskripsi' => $this->faker->sentence(12),
                'alamat'    => $this->faker->address(),
                'no_hp'     => '08' . $this->faker->numberBetween(1000000000, 9999999999),
                'banjar_id' => $b->id,
                'status'    => 'aktif',
            ], $this->coord()));

            $this->makeUser([
                'name'  => 'Pemilik ' . $nama,
                'email' => 'umkm' . $i . '@mail.test',
            ], 'umkm', ['umkm_id' => $umkm->id]);

            foreach ($produkList as $namaProduk) {
                $product = Product::create([
                    'umkm_id'    => $umkm->id,
                    'kategori_id' => $this->faker->randomElement($categories),
                    'nama'       => $namaProduk,
                    'deskripsi'  => $this->faker->sentence(14),
                    'harga'      => $this->faker->randomElement([15000, 25000, 35000, 50000, 75000, 120000]),
                    'stok'       => $this->faker->numberBetween(5, 60),
                    'berat'      => $this->faker->numberBetween(200, 3000),
                    'is_active'  => true,
                ]);
                for ($img = 0; $img < 2; $img++) {
                    ProductImage::create(['product_id' => $product->id, 'path' => 'products/placeholder-' . $this->faker->numberBetween(1, 8) . '.jpg']);
                }
                $products[] = $product;
            }

            $umkms[] = $umkm;
        }

        return [$umkms, $products];
    }

    private function seedDeposits(array $bankSampahs, array $petugasBS, array $masyarakat, array $wastePrices): void
    {
        foreach ($masyarakat as $warga) {
            $jumlahSetor = $this->faker->numberBetween(1, 4);
            for ($d = 0; $d < $jumlahSetor; $d++) {
                $bs = $this->faker->randomElement($bankSampahs);
                $petugas = $this->faker->randomElement($petugasBS[$bs->id]);
                $tanggal = now()->subDays($this->faker->numberBetween(0, 90));

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
                $picked = collect($wastePrices)->random($this->faker->numberBetween(1, 3));
                foreach ($picked as $price) {
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
        $kandidat = collect($masyarakat)->filter(fn ($u) => ($this->saldo[$u->id] ?? 0) > 30000)->values();

        foreach ($kandidat->random(min(12, $kandidat->count())) as $warga) {
            $maks = (float) ($this->saldo[$warga->id] ?? 0);
            $jumlah = min($maks, $this->faker->randomElement([25000, 50000, 75000, 100000]));
            if ($jumlah < 10000) {
                continue;
            }

            $status = $this->faker->randomElement(['menunggu', 'menunggu', 'disetujui', 'ditolak', 'selesai']);

            $w = Withdrawal::create([
                'user_id'     => $warga->id,
                'jumlah'      => $jumlah,
                'metode'      => 'transfer_bank',
                'no_rekening' => 'BNI ' . $this->faker->numerify('##########'),
                'status'      => $status,
                'approved_by' => in_array($status, ['disetujui', 'selesai', 'ditolak']) ? null : null,
                'catatan'     => $status === 'ditolak' ? 'Rekening tidak sesuai nama nasabah.' : null,
            ]);

            if (in_array($status, ['disetujui', 'selesai'])) {
                $this->wallet($warga, -$jumlah, 'penarikan', $w, 'Penarikan saldo #' . $w->id);
            }
        }
    }

    private function seedOrders(array $masyarakat, array $umkms, array $products): void
    {
        $byUmkm = collect($products)->groupBy('umkm_id');

        for ($i = 0; $i < 20; $i++) {
            $umkm = $this->faker->randomElement($umkms);
            $daftarProduk = $byUmkm->get($umkm->id);
            if (! $daftarProduk || $daftarProduk->isEmpty()) {
                continue;
            }
            $buyer = $this->faker->randomElement($masyarakat);

            $pilih = $daftarProduk->random($this->faker->numberBetween(1, min(3, $daftarProduk->count())));
            $ongkir = $this->faker->randomElement([10000, 15000, 20000]);
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

            // Bila bayar saldo tapi saldo kurang, jadikan menunggu_bayar
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
                'alamat_kirim'   => $this->faker->address(),
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

    private function seedReports(array $masyarakat, array $petugasLapangan, array $banjars): void
    {
        $kategori = ReportCategory::pluck('id')->all();
        $judulContoh = [
            'Tumpukan sampah di pinggir jalan', 'Pembakaran sampah dekat permukiman',
            'Saluran got tersumbat sampah plastik', 'Sampah berserakan di pantai',
            'TPS melebihi kapasitas', 'Pembuangan sampah liar di lahan kosong',
        ];

        for ($i = 0; $i < 18; $i++) {
            $pelapor = $this->faker->randomElement($masyarakat);
            $b = $this->faker->randomElement($banjars);
            $c = $this->coord();
            $status = $this->faker->randomElement(['menunggu', 'diverifikasi', 'ditugaskan', 'proses', 'selesai', 'ditolak']);

            $report = Report::create([
                'pelapor_id'  => $pelapor->id,
                'kategori_id' => $this->faker->randomElement($kategori),
                'tiket_no'    => 'RPT-' . now()->format('Ym') . '-' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'judul'       => $this->faker->randomElement($judulContoh),
                'deskripsi'   => $this->faker->paragraph(3),
                'lat'         => $c['lat'],
                'lng'         => $c['lng'],
                'alamat'      => $this->faker->address(),
                'banjar_id'   => $b->id,
                'status'      => $status,
                'is_duplikat' => false,
                'verified_by' => in_array($status, ['diverifikasi', 'ditugaskan', 'proses', 'selesai']) ? null : null,
                'created_at'  => now()->subDays($this->faker->numberBetween(0, 60)),
            ]);

            if (in_array($status, ['ditugaskan', 'proses', 'selesai'])) {
                $petugas = $this->faker->randomElement($petugasLapangan);
                ReportAssignment::create([
                    'report_id'   => $report->id,
                    'petugas_id'  => $petugas->id,
                    'assigned_by' => null,
                    'status'      => $status === 'selesai' ? 'selesai' : ($status === 'proses' ? 'dikerjakan' : 'ditugaskan'),
                    'assigned_at' => now()->subDays($this->faker->numberBetween(0, 30)),
                ]);

                if (in_array($status, ['proses', 'selesai'])) {
                    ReportProgress::create([
                        'report_id'       => $report->id,
                        'petugas_id'      => $petugas->id,
                        'catatan'         => $status === 'selesai' ? 'Sampah sudah diangkut dan area dibersihkan.' : 'Sedang dalam penanganan.',
                        'foto_bukti'      => 'reports/bukti-' . $this->faker->numberBetween(1, 6) . '.jpg',
                        'status_progress' => $status === 'selesai' ? 'selesai' : 'dikerjakan',
                        'lat'             => $c['lat'],
                        'lng'             => $c['lng'],
                    ]);
                }
            }
        }
    }

    private function seedMisc(array $masyarakat): void
    {
        // Klasifikasi AI
        $jenis = ['Botol Plastik PET', 'Kardus', 'Kaleng Aluminium', 'Organik', 'Kaca'];
        for ($i = 0; $i < 12; $i++) {
            $u = $this->faker->randomElement($masyarakat);
            WasteClassification::create([
                'user_id'                 => $u->id,
                'image_path'              => 'classifications/sample-' . $this->faker->numberBetween(1, 10) . '.jpg',
                'hasil_jenis'             => $this->faker->randomElement($jenis),
                'kategori'                => $this->faker->randomElement(['Anorganik', 'Organik', 'B3']),
                'confidence'             => $this->faker->randomFloat(3, 0.6, 0.99),
                'langkah_pengolahan'      => ['Bersihkan', 'Keringkan', 'Pilah sesuai jenis', 'Setor ke bank sampah'],
                'rekomendasi_daur_ulang'  => $this->faker->sentence(12),
                'raw_response'            => ['label' => 'sample', 'score' => 0.9],
            ]);
        }

        // Artikel edukasi
        $penulis = User::role('admin_dinas')->first() ?? User::role('admin')->first();
        $judulArtikel = [
            'Mengenal Ekonomi Sirkular Sampah', 'Cara Memilah Sampah Rumah Tangga',
            'Manfaat Bank Sampah bagi Warga', 'Panduan Setor Sampah di Niti Resik',
            'Sad Kerthi & Pengelolaan Lingkungan', 'Tips Mengurangi Sampah Plastik',
        ];
        foreach ($judulArtikel as $i => $judul) {
            Article::create([
                'author_id'    => $penulis?->id,
                'tipe'         => $this->faker->randomElement(['artikel', 'panduan', 'tutorial']),
                'judul'        => $judul,
                'slug'         => Str::slug($judul) . '-' . ($i + 1),
                'konten'       => $this->faker->paragraphs(5, true),
                'status'       => 'published',
                'published_at' => now()->subDays($this->faker->numberBetween(1, 120)),
            ]);
        }

        // Notifikasi in-app
        foreach (collect($masyarakat)->random(20) as $u) {
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
}