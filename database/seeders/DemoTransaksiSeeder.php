<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\MetodeBayar;
use App\Enums\StatusPembayaran;
use App\Enums\StatusPesanan;
use App\Enums\TipeTransaksiDompet;
use App\Models\BankSampah;
use App\Models\BankSampahHarga;
use App\Models\Keranjang;
use App\Models\Pembayaran;
use App\Models\Pesanan;
use App\Models\PesananItem;
use App\Models\Produk;
use App\Models\SetoranSampah;
use App\Models\Tps;
use App\Models\Umkm;
use App\Models\User;
use App\Services\Marketplace\PesananService;
use App\Services\Tps\TpsService;
use App\Services\Wallet\DompetService;
use App\Services\Wallet\PenarikanService;
use App\Services\Wallet\SetoranService;
use App\Services\Wallet\UmkmDompetService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Transaksi yang memindahkan uang dan stok.
 *
 * Dipisahkan dari DemoSeeder karena seluruhnya bergantung pada akun,
 * katalog harga, dan produk yang baru ada setelah berkas itu selesai.
 *
 * ## Kenapa lewat Service, bukan insert langsung
 *
 * Saldo dompet, mutasi, snapshot harga, dan potongan stok saling terikat.
 * Menuliskannya langsung ke tabel berarti menyalin ulang aturan yang
 * sudah ada di Service, dan salinan itu pasti menyimpang cepat atau
 * lambat, menghasilkan basis data demo yang angkanya tidak konsisten
 * dengan aturan aplikasinya sendiri.
 *
 * Pengecualiannya hanya pembuatan pesanan. CheckoutService memanggil
 * penyedia ongkir lewat jaringan, dan seeder tidak boleh bergantung pada
 * layanan luar yang bisa mati. Ongkir pesanan demo karena itu ditetapkan
 * sebagai angka tetap, sementara seluruh perpindahan uang dan stoknya
 * tetap memakai Service yang sama dengan alur sungguhan.
 */
class DemoTransaksiSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('DemoTransaksiSeeder tidak boleh dijalankan di produksi.');

            return;
        }

        $this->semaiSetoran();
        $this->semaiKeanggotaanTps();
        $this->semaiPesanan();
        $this->semaiKeranjang();
        $this->semaiPenarikan();

        $this->command?->info('Transaksi demo siap: setoran, iuran TPS, pesanan, dan penarikan.');
    }

    // ----------------------------------------------------------------
    // Bank sampah
    // ----------------------------------------------------------------

    /**
     * Setoran sampah yang benar-benar mengisi saldo nasabah.
     *
     * Tanpa ini seluruh dompet bernilai nol, dan pembayaran memakai saldo
     *, jalur pembayaran utama produk ini, tidak bisa diperagakan sama
     * sekali.
     */
    private function semaiSetoran(): void
    {
        $service = app(SetoranService::class);

        /*
         * Beberapa setoran per nasabah, bukan satu.
         *
         * Saldo bank sampah tidak pernah terkumpul sekaligus: ia
         * menumpuk dari kunjungan berulang selama berbulan-bulan.
         * Sekali setor juga tidak akan pernah cukup untuk memperagakan
         * pembayaran memakai saldo, dan demo yang saldonya selalu kurang
         * membuat jalur pembayaran utama produk ini tidak bisa
         * ditunjukkan sama sekali.
         */
        $rencana = [
            // Ni Kadek Sari Dewi, nasabah paling rajin.
            ['Bank Sampah Sari Lestari', 'banksampah.badung@resikita.id', 'warga.kadek@resikita.id',
                [['Botol PET bening', 6.5], ['Kardus', 12.0], ['Kaleng aluminium', 1.8]]],
            ['Bank Sampah Sari Lestari', 'banksampah.badung@resikita.id', 'warga.kadek@resikita.id',
                [['Kertas HVS', 4.2], ['Botol PET bening', 3.1], ['Koran bekas', 6.0]]],
            ['Bank Sampah Sari Lestari', 'banksampah.badung@resikita.id', 'warga.kadek@resikita.id',
                [['Kardus', 18.0], ['Kaleng aluminium', 2.5], ['Minyak jelantah', 3.0]]],
            ['Bank Sampah Sari Lestari', 'banksampah.badung@resikita.id', 'warga.kadek@resikita.id',
                [['Botol PET bening', 9.0], ['Plastik HDPE', 6.0]]],

            // I Wayan Gede Suparta.
            ['Bank Sampah Sari Lestari', 'banksampah.badung@resikita.id', 'warga.wayan@resikita.id',
                [['Kardus', 20.0], ['Koran bekas', 7.5], ['Minyak jelantah', 2.0]]],
            ['Bank Sampah Sari Lestari', 'banksampah.badung@resikita.id', 'warga.wayan@resikita.id',
                [['Kaleng aluminium', 4.0], ['Botol PET bening', 8.0]]],
            ['Bank Sampah Sari Lestari', 'banksampah.badung@resikita.id', 'warga.wayan@resikita.id',
                [['Kardus', 25.0], ['Plastik HDPE', 7.0], ['Elektronik kecil', 2.0]]],

            // Komang Ayu Lestari.
            ['Bank Sampah Sari Lestari', 'banksampah.badung@resikita.id', 'warga.komang@resikita.id',
                [['Plastik HDPE', 5.0], ['Beling', 9.0], ['Baterai bekas', 0.4]]],
            ['Bank Sampah Sari Lestari', 'banksampah.badung@resikita.id', 'warga.komang@resikita.id',
                [['Kardus', 15.0], ['Botol PET bening', 7.0], ['Kaleng aluminium', 2.0]]],
            ['Bank Sampah Sari Lestari', 'banksampah.badung@resikita.id', 'warga.komang@resikita.id',
                [['Koran bekas', 10.0], ['Kertas HVS', 5.0]]],

            // Siti Nurhaliza Rahmawati, nasabah unit Sleman.
            ['Bank Sampah Guyub Rukun', 'banksampah.sleman@resikita.id', 'warga.siti@resikita.id',
                [['Botol PET bening', 8.0], ['Kaleng aluminium', 2.5], ['Elektronik kecil', 1.2]]],
            ['Bank Sampah Guyub Rukun', 'banksampah.sleman@resikita.id', 'warga.siti@resikita.id',
                [['Kardus', 22.0], ['Kertas HVS', 6.0]]],
            ['Bank Sampah Guyub Rukun', 'banksampah.sleman@resikita.id', 'warga.siti@resikita.id',
                [['Botol PET bening', 10.0], ['Minyak jelantah', 4.0]]],

            // Budi Santoso.
            ['Bank Sampah Guyub Rukun', 'banksampah.sleman@resikita.id', 'warga.budi@resikita.id',
                [['Kardus', 15.0], ['Minyak jelantah', 3.5]]],
            ['Bank Sampah Guyub Rukun', 'banksampah.sleman@resikita.id', 'warga.budi@resikita.id',
                [['Botol PET bening', 6.0], ['Kaleng aluminium', 1.5]]],
        ];

        foreach ($rencana as [$namaBank, $emailPetugas, $emailNasabah, $item]) {
            $bank = BankSampah::query()->where('nama', $namaBank)->first();
            $petugas = User::query()->where('email', $emailPetugas)->first();
            $nasabah = User::query()->where('email', $emailNasabah)->first();

            if ($bank === null || $petugas === null || $nasabah === null) {
                continue;
            }

            // Idempoten: satu nasabah cukup punya setoran sebanyak yang
            // direncanakan, tidak bertambah tiap seeder dijalankan ulang.
            $sudahAda = SetoranSampah::query()
                ->where('bank_sampah_id', $bank->id)
                ->where('nasabah_id', $nasabah->id)
                ->count();

            $direncanakan = collect($rencana)
                ->filter(fn (array $r): bool => $r[0] === $namaBank && $r[2] === $emailNasabah)
                ->count();

            if ($sudahAda >= $direncanakan) {
                continue;
            }

            $setoran = $service->mulai($bank, $petugas, $nasabah);

            foreach ($item as [$jenis, $berat]) {
                $harga = BankSampahHarga::query()
                    ->where('bank_sampah_id', $bank->id)
                    ->where('jenis_sampah', $jenis)
                    ->first();

                if ($harga !== null) {
                    $service->tambahItem($setoran, $harga, $berat);
                }
            }

            $service->selesaikan($setoran->fresh());
        }
    }

    // ----------------------------------------------------------------
    // TPS
    // ----------------------------------------------------------------

    private function semaiKeanggotaanTps(): void
    {
        $service = app(TpsService::class);
        $dompet = app(DompetService::class);

        $rencana = [
            ['TPS3R Mengwi Bersih', 'warga.kadek@resikita.id', true],
            ['TPS3R Mengwi Bersih', 'warga.wayan@resikita.id', false],
            ['TPS3R Ngaglik Asri', 'warga.siti@resikita.id', true],
        ];

        foreach ($rencana as [$namaTps, $email, $lunasiBulanIni]) {
            $tps = Tps::query()->where('nama', $namaTps)->first();
            $user = User::query()->where('email', $email)->first();

            if ($tps === null || $user === null) {
                continue;
            }

            $anggota = $service->keanggotaan($user);

            if ($anggota === null) {
                $anggota = $service->gabung($tps, $user);
            }

            // Dua periode: bulan lalu dan bulan ini. Tagihan bulan lalu
            // selalu dilunasi supaya riwayat iuran tidak kosong; bulan ini
            // sengaja disisakan menunggu pada sebagian warga agar tagihan
            // berjalan juga punya contoh.
            $bulanLalu = now()->subMonth()->format('Y-m');
            $bulanIni = now()->format('Y-m');

            $tagihanLalu = $service->terbitkanTagihan($anggota, $bulanLalu);

            if ($tagihanLalu->status->bisaDibayar() && $dompet->cukup($user, $tagihanLalu->jumlah)) {
                $service->bayarDenganSaldo($tagihanLalu, $user);
            }

            $tagihanIni = $service->terbitkanTagihan($anggota, $bulanIni);

            if ($lunasiBulanIni && $tagihanIni->status->bisaDibayar() && $dompet->cukup($user, $tagihanIni->jumlah)) {
                $service->bayarDenganSaldo($tagihanIni, $user);
            }
        }
    }

    // ----------------------------------------------------------------
    // Marketplace
    // ----------------------------------------------------------------

    /**
     * Pesanan di seluruh tahap daur hidupnya.
     *
     * Panel penjual dan riwayat pembeli baru terasa benar kalau setiap
     * kolom statusnya punya isi. Yang berstatus selesai juga menjadi
     * satu-satunya pintu menuju ulasan produk.
     */
    private function semaiPesanan(): void
    {
        $pesananService = app(PesananService::class);
        $dompet = app(DompetService::class);

        /*
         * Satu pesanan untuk tiap tahap daur hidup, dan dua di antaranya
         * benar-benar selesai, satu di tiap toko aktif. Pesanan selesai
         * adalah satu-satunya sumber saldo penjual sekaligus satu-satunya
         * pintu menuju ulasan produk, jadi tanpa keduanya dompet UMKM dan
         * halaman ulasan sama-sama kosong.
         */
        $rencana = [
            // [email pembeli, nama toko, [[slug produk, qty]], ongkir, metode, status akhir]
            ['warga.kadek@resikita.id', 'Rumah Daur Sleman', [['dompet-kain-perca-batik', 1], ['pot-gantung-botol-pet', 2]], 18_000, MetodeBayar::Saldo, StatusPesanan::Selesai],
            ['warga.wayan@resikita.id', 'Kriya Sampah Mengwi', [['pot-tanaman-ban-bekas', 1], ['kompos-padat-5-kilogram', 2]], 15_000, MetodeBayar::Saldo, StatusPesanan::Selesai],
            ['warga.budi@resikita.id', 'Rumah Daur Sleman', [['keranjang-koran-gulung', 1]], 18_000, MetodeBayar::Saldo, StatusPesanan::Dikirim],
            ['warga.komang@resikita.id', 'Rumah Daur Sleman', [['pot-gantung-botol-pet', 3]], 20_000, MetodeBayar::Saldo, StatusPesanan::Dikemas],
            ['warga.siti@resikita.id', 'Rumah Daur Sleman', [['tas-anyam-sachet-motif-parang', 1]], 16_000, MetodeBayar::Saldo, StatusPesanan::Dibayar],
            ['warga.budi@resikita.id', 'Kriya Sampah Mengwi', [['lampu-meja-botol-kaca', 1]], 22_000, MetodeBayar::Midtrans, StatusPesanan::MenungguBayar],
        ];

        foreach ($rencana as [$emailPembeli, $namaToko, $isi, $ongkir, $metode, $statusAkhir]) {
            $pembeli = User::query()->where('email', $emailPembeli)->first();
            $umkm = Umkm::query()->where('nama', $namaToko)->first();

            if ($pembeli === null || $umkm === null) {
                continue;
            }

            // Idempoten: satu pembeli satu pesanan per toko di data demo.
            if (Pesanan::query()->where('user_id', $pembeli->id)->where('umkm_id', $umkm->id)->exists()) {
                continue;
            }

            $baris = [];
            $subtotal = 0;

            foreach ($isi as [$slug, $qty]) {
                $produk = Produk::query()->where('slug', $slug)->first();

                if ($produk === null || $produk->stok < $qty) {
                    continue;
                }

                $baris[] = [$produk, $qty];
                $subtotal += $produk->harga * $qty;
            }

            if ($baris === []) {
                continue;
            }

            $total = $subtotal + $ongkir;

            // Pembayaran saldo hanya masuk akal kalau saldonya memang ada.
            // Kalau kurang, pesanannya dilewati alih-alih memaksa dompet
            // menjadi negatif, dompet minus tidak pernah sah di aplikasi
            // ini, dan data demo tidak boleh menjadi satu-satunya tempat
            // keadaan itu bisa muncul.
            if ($metode === MetodeBayar::Saldo && ! $dompet->cukup($pembeli, $total)) {
                $this->command?->warn("Saldo {$pembeli->name} tidak cukup untuk pesanan demo, dilewati.");

                continue;
            }

            $pesanan = Pesanan::create([
                'kode' => 'RSK-ORD-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                'user_id' => $pembeli->id,
                'umkm_id' => $umkm->id,
                'subtotal' => $subtotal,
                'ongkir' => $ongkir,
                'total' => $total,
                'metode_bayar' => $metode,
                'status' => StatusPesanan::MenungguBayar,
                'nama_penerima' => $pembeli->name,
                'phone_penerima' => $pembeli->phone ?? '081200000000',
                'alamat_kirim' => 'Jalan Contoh Nomor 10, '.($pembeli->wilayah?->nama ?? 'Indonesia'),
                'destination_id' => 17_473,
                'kurir' => 'jne',
                'layanan_kurir' => 'REG',
            ]);

            foreach ($baris as [$produk, $qty]) {
                PesananItem::create([
                    'pesanan_id' => $pesanan->id,
                    'produk_id' => $produk->id,
                    'nama_snapshot' => $produk->nama,
                    'harga_snapshot' => $produk->harga,
                    'qty' => $qty,
                    'subtotal' => $produk->harga * $qty,
                ]);

                // Stok dipotong sejak checkout, sama seperti alur nyata.
                $produk->decrement('stok', $qty);
            }

            $pembayaran = Pembayaran::create([
                'payable_type' => $pesanan->getMorphClass(),
                'payable_id' => $pesanan->id,
                'metode' => $metode->value,
                'jumlah' => $total,
                'status' => StatusPembayaran::Pending,
            ]);

            if ($statusAkhir === StatusPesanan::MenungguBayar) {
                $pesanan->update(['snap_token' => 'demo-snap-'.Str::lower(Str::random(20))]);
                $pembayaran->update(['midtrans_order_id' => $pesanan->kode]);

                continue;
            }

            // Terbayar: saldo pembeli benar-benar berkurang lewat Service.
            $dompet->debit(
                $pembeli,
                $total,
                TipeTransaksiDompet::Belanja,
                $pesanan,
                'Pembelian pesanan '.$pesanan->kode,
            );

            $pesanan->update(['status' => StatusPesanan::Dibayar, 'dibayar_at' => now()->subDays(6)]);
            $pembayaran->update(['status' => StatusPembayaran::Paid, 'dibayar_at' => now()->subDays(6)]);

            if ($statusAkhir === StatusPesanan::Dibayar) {
                continue;
            }

            $pesananService->tandaiDikemas($pesanan->fresh());

            if ($statusAkhir === StatusPesanan::Dikemas) {
                continue;
            }

            $pesananService->tandaiDikirim($pesanan->fresh(), 'JNE'.random_int(100000000, 999999999));

            if ($statusAkhir === StatusPesanan::Dikirim) {
                continue;
            }

            // Menyelesaikan pesanan yang meneruskan subtotal ke dompet
            // penjual, sumber saldo UMKM satu-satunya.
            $pesananService->tandaiSelesai($pesanan->fresh());

            $this->semaiUlasan($pesananService, $pesanan->fresh(), $pembeli, $baris);
        }
    }

    /**
     * @param  array<int, array{0: Produk, 1: int}>  $baris
     */
    private function semaiUlasan(PesananService $service, Pesanan $pesanan, User $pembeli, array $baris): void
    {
        $komentar = [
            'Jahitannya rapi dan bahannya benar-benar dari perca. Pengiriman cepat, dikemas aman.',
            'Sesuai foto. Senang tahu uangnya kembali ke perajinnya langsung.',
            'Kuat dipakai harian. Sudah dua kali pesan dan tidak pernah mengecewakan.',
            'Dikemas rapi pakai kardus bekas, bukan bubble wrap baru. Konsisten sampai ke pengemasan.',
        ];

        foreach ($baris as $i => [$produk, $qty]) {
            $service->tulisUlasan($pesanan, $pembeli, [
                'produk_id' => $produk->id,
                'rating' => 5 - ($i % 2),
                'komentar' => $komentar[$i % count($komentar)],
            ]);

            unset($qty);
        }
    }

    /** Dua keranjang berisi, supaya halaman keranjang tidak kosong saat dibuka. */
    private function semaiKeranjang(): void
    {
        $rencana = [
            ['warga.kadek@resikita.id', [['tikar-lipat-sachet', 1], ['keranjang-koran-gulung', 2]]],
            ['warga.budi@resikita.id', [['jam-dinding-serbuk-kayu', 1]]],
        ];

        foreach ($rencana as [$email, $isi]) {
            $user = User::query()->where('email', $email)->first();

            if ($user === null) {
                continue;
            }

            foreach ($isi as [$slug, $qty]) {
                $produk = Produk::query()->where('slug', $slug)->first();

                if ($produk === null || $produk->stok < $qty) {
                    continue;
                }

                Keranjang::updateOrCreate(
                    ['user_id' => $user->id, 'produk_id' => $produk->id],
                    ['qty' => $qty],
                );
            }
        }
    }

    // ----------------------------------------------------------------
    // Penarikan saldo
    // ----------------------------------------------------------------

    private function semaiPenarikan(): void
    {
        $service = app(PenarikanService::class);
        $dompet = app(DompetService::class);

        $admin = User::query()->where('email', 'admin@resikita.id')->first();
        $warga = User::query()->where('email', 'warga.wayan@resikita.id')->first();

        if ($warga !== null && $admin !== null && $dompet->saldo($warga) >= 50_000) {
            $adaPenarikan = $warga->penarikan()->exists();

            if (! $adaPenarikan) {
                $penarikan = $service->ajukan($warga, [
                    'jumlah' => 50_000,
                    'nama_bank' => 'BRI',
                    'no_rekening' => '004501012345501',
                    'atas_nama' => $warga->name,
                ]);

                $service->setujui($penarikan, $admin);
                $service->tandaiSelesai($penarikan->fresh(), 'Ditransfer lewat BRI, referensi TRX-DEMO-0001.');
            }
        }

        // Satu pengajuan yang sengaja dibiarkan menunggu, supaya halaman
        // persetujuan penarikan di panel admin punya isi.
        $wargaKedua = User::query()->where('email', 'warga.komang@resikita.id')->first();

        if ($wargaKedua !== null && $dompet->saldo($wargaKedua) >= 25_000 && ! $wargaKedua->penarikan()->exists()) {
            $service->ajukan($wargaKedua, [
                'jumlah' => 25_000,
                'nama_bank' => 'BCA',
                'no_rekening' => '7720345566',
                'atas_nama' => $wargaKedua->name,
            ]);
        }

        // Penarikan sisi penjual.
        $umkm = Umkm::query()->where('nama', 'Rumah Daur Sleman')->first();

        if ($umkm !== null && ! $umkm->penarikan()->exists()) {
            $saldoUmkm = app(UmkmDompetService::class)->saldo($umkm);

            if ($saldoUmkm >= 50_000) {
                $service->ajukanUmkm($umkm, [
                    'jumlah' => 50_000,
                    'nama_bank' => 'Mandiri',
                    'no_rekening' => '1370012345678',
                    'atas_nama' => 'Endang Kusumawati',
                ]);
            }
        }
    }
}
