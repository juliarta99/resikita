<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\Role;
use App\Http\Controllers\Auth\KeluarController;
use App\Livewire\Admin;
use App\Livewire\Asisten;
use App\Livewire\Auth;
use App\Livewire\BankSampah;
use App\Livewire\Fasilitator;
use App\Livewire\Pemerintahan;
use App\Livewire\Profil;
use App\Livewire\Publik;
use App\Livewire\Umkm;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Route web (Livewire)
|--------------------------------------------------------------------------
|
| Kanal untuk pemerintahan, fasilitator wilayah, bank sampah, UMKM, dan
| admin. Masyarakat dan petugas lapangan tidak punya panel web, keduanya
| memakai aplikasi resikita-mobile lewat /api/v1 (CLAUDE.md 6.2).
|
| Sanctum dipakai dalam mode sesi di sini dan mode token di api.php. SPA
| mode tidak dipakai di mana pun.
|
| Tiga role pemerintahan memakai komponen yang sama persis. Yang berbeda
| hanya cakupan datanya, dan itu ditentukan WilayahScopeService dari
| `users.wilayah_id`, bukan oleh percabangan di komponen. Lihat
| CLAUDE.md 8.1 untuk alasan lengkapnya.
|
*/

/*
|--------------------------------------------------------------------------
| Publik, tanpa akun
|--------------------------------------------------------------------------
|
| Direktori, literasi, marketplace, dan papan laporan sengaja terbuka.
| Warga harus bisa menemukan bank sampah terdekat sebelum memutuskan
| mendaftar, dan penanganan laporan yang mandek harus terlihat oleh
| siapa saja, termasuk oleh atasan pihak yang seharusnya menanganinya.
|
*/

Route::name('publik.')->group(function (): void {
    Route::get('/', Publik\Beranda::class)->name('beranda');

    Route::get('/artikel', Publik\ArtikelIndex::class)->name('artikel');
    Route::get('/artikel/{artikel}', Publik\ArtikelShow::class)->name('artikel.show');

    Route::get('/direktori', Publik\Direktori::class)->name('direktori');

    Route::get('/produk', Publik\ProdukIndex::class)->name('produk');
    Route::get('/produk/{produk}', Publik\ProdukShow::class)->name('produk.show');

    Route::get('/laporan', Publik\LaporanIndex::class)->name('laporan');
    Route::get('/laporan/{laporan}', Publik\LaporanShow::class)->name('laporan.show')->whereNumber('laporan');

    Route::get('/daftarkan-wilayah', Publik\PengajuanWilayah::class)->name('pengajuan-wilayah');
    Route::get('/daftarkan-umkm', Publik\PendaftaranUmkm::class)->name('pendaftaran-umkm');
});

// Nama `beranda` dipertahankan sebagai alias supaya tautan lama dan
// pengalihan bawaan Laravel tetap menemukan halaman muka.
Route::get('/beranda', fn () => redirect()->route('publik.beranda'))->name('beranda');

/*
|--------------------------------------------------------------------------
| Tamu
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function (): void {
    Route::get('/masuk', Auth\Login::class)->name('masuk');
    Route::get('/lupa-password', Auth\LupaPassword::class)->name('lupa-password');
    Route::get('/reset-password', Auth\ResetPassword::class)->name('reset-password');
});

Route::post('/keluar', KeluarController::class)->middleware('auth')->name('keluar');

/*
|--------------------------------------------------------------------------
| Perlu masuk
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function (): void {

    /*
    |----------------------------------------------------------------
    | Pemerintahan, satu set komponen, tiga role
    |----------------------------------------------------------------
    |
    | URL dan nama route terpisah supaya tiap role punya alamatnya
    | sendiri dan middleware role menjaga pintunya masing-masing.
    */
    $panelPemerintahan = function (): void {
        Route::get('/', Pemerintahan\Dashboard::class)->name('dashboard');
        Route::get('/laporan', Pemerintahan\LaporanManager::class)->name('laporan');
        Route::get('/laporan/{laporan}', Pemerintahan\LaporanDetail::class)->name('laporan.detail');
        Route::get('/petugas', Pemerintahan\PetugasManager::class)->name('petugas');
        Route::get('/tps', Pemerintahan\TpsManager::class)->name('tps');
        Route::get('/analitik', Pemerintahan\Analitik::class)->name('analitik');
        Route::get('/profil', Profil::class)->name('profil');
    };

    Route::middleware('role:'.Role::AdminProvinsi->value)
        ->prefix('provinsi')->name('provinsi.')->group($panelPemerintahan);

    Route::middleware('role:'.Role::AdminKabupaten->value)
        ->prefix('kabupaten')->name('kabupaten.')->group($panelPemerintahan);

    Route::middleware('role:'.Role::KepalaDesa->value)
        ->prefix('desa')->name('desa.')->group($panelPemerintahan);

    /*
    |----------------------------------------------------------------
    | Fasilitator Wilayah
    |----------------------------------------------------------------
    */
    Route::middleware('role:'.Role::FasilitatorWilayah->value)
        ->prefix('fasilitator')->name('fasilitator.')
        ->group(function (): void {
            Route::get('/', Fasilitator\Dashboard::class)->name('dashboard');
            Route::get('/laporan', Fasilitator\PapanLaporan::class)->name('laporan');
            Route::get('/laporan/{laporan}', Fasilitator\LaporanDetail::class)->name('laporan.detail');
            Route::get('/prioritas', Fasilitator\PapanPrioritas::class)->name('prioritas');
            Route::get('/profil', Profil::class)->name('profil');
        });

    /*
    |----------------------------------------------------------------
    | Bank sampah
    |----------------------------------------------------------------
    */
    Route::middleware('role:'.Role::BankSampah->value)
        ->prefix('bank-sampah')->name('bank-sampah.')
        ->group(function (): void {
            Route::get('/', BankSampah\Dashboard::class)->name('dashboard');
            Route::get('/setoran', BankSampah\Setoran::class)->name('setoran');
            Route::get('/harga', BankSampah\HargaManager::class)->name('harga');
            Route::get('/nasabah', BankSampah\Nasabah::class)->name('nasabah');
            Route::get('/riwayat', BankSampah\Riwayat::class)->name('riwayat');
            Route::get('/asisten', Asisten::class)->name('asisten')
                ->middleware('permission:'.Permission::ChatbotPakai->value);
            Route::get('/profil', Profil::class)->name('profil');
        });

    /*
    |----------------------------------------------------------------
    | UMKM
    |----------------------------------------------------------------
    */
    /*
    | Dua lapis penjagaan yang berbeda maksudnya.
    |
    | `role:umkm` menentukan siapa yang boleh berada di sini sama sekali.
    | `toko.terverifikasi` menentukan apakah tokonya sudah layak
    | berdagang. Halaman status pendaftaran dan profil pribadi sengaja di
    | luar lapis kedua: keduanya justru yang dibutuhkan pemilik toko yang
    | pendaftarannya masih ditinjau atau ditolak.
    */
    Route::middleware('role:'.Role::Umkm->value)
        ->prefix('umkm')->name('umkm.')
        ->group(function (): void {
            Route::get('/status', Umkm\StatusPendaftaran::class)->name('status');
            Route::get('/profil', Profil::class)->name('profil');

            Route::middleware('toko.terverifikasi')->group(function (): void {
                Route::get('/', Umkm\Dashboard::class)->name('dashboard');
                Route::get('/toko', Umkm\Toko::class)->name('toko');
                Route::get('/produk', Umkm\ProdukManager::class)->name('produk');
                Route::get('/pesanan', Umkm\PesananManager::class)->name('pesanan');
                Route::get('/konten', Umkm\KontenPromosi::class)->name('konten')
                    ->middleware('permission:'.Permission::KontenPromosiBuat->value);
                Route::get('/saldo', Umkm\Saldo::class)->name('saldo');
                Route::get('/asisten', Asisten::class)->name('asisten')
                    ->middleware('permission:'.Permission::ChatbotPakai->value);
            });
        });

    /*
    |----------------------------------------------------------------
    | Admin dan super admin
    |----------------------------------------------------------------
    */
    Route::middleware('role:'.Role::SuperAdmin->value.'|'.Role::Admin->value)
        ->prefix('admin')->name('admin.')
        ->group(function (): void {
            Route::get('/', Admin\Dashboard::class)->name('dashboard');
            Route::get('/laporan', Admin\LaporanManager::class)->name('laporan');
            Route::get('/pengguna', Admin\PenggunaManager::class)->name('pengguna');
            Route::get('/umkm', Admin\UmkmManager::class)->name('umkm');
            Route::get('/artikel', Admin\ArtikelManager::class)->name('artikel');
            Route::get('/penarikan', Admin\PenarikanManager::class)->name('penarikan');
            Route::get('/master', Admin\MasterData::class)->name('master');
            Route::get('/profil', Profil::class)->name('profil');

            /*
            | Hanya super admin. Yang pertama menentukan siapa memegang
            | kewenangan atas sebuah daerah; yang kedua dan ketiga berisi
            | data lintas seluruh platform, termasuk jejak tindakan
            | sesama admin.
            */
            Route::middleware('role:'.Role::SuperAdmin->value)->group(function (): void {
                Route::get('/pengajuan-wilayah', Admin\PengajuanWilayahManager::class)->name('pengajuan-wilayah');
                Route::get('/wilayah', Admin\WilayahManager::class)->name('wilayah');
                Route::get('/log', Admin\LogAktivitas::class)->name('log');
            });
        });
});
