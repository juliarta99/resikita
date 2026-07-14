<?php

use App\Http\Controllers\Auth\LogoutController;
use App\Livewire\Admin\ArticleManager;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\ProductManager as AdminProductManager;
use App\Livewire\Admin\Profil as AdminProfil;
use App\Livewire\Admin\ReportManager;
use App\Livewire\Admin\BankSampahManager;
use App\Livewire\Admin\BanjarDinasManager;
use App\Livewire\Admin\HargaSampahManager;
use App\Livewire\Admin\KecamatanManager;
use App\Livewire\Admin\KelurahanManager;
use App\Livewire\Admin\ProductCategoryManager;
use App\Livewire\Admin\ReportCategoryManager;
use App\Livewire\Admin\TpsManager;
use App\Livewire\Admin\UmkmManager;
use App\Livewire\Admin\UserManager;
use App\Livewire\Dinas\Dashboard as DinasDashboard;
use App\Livewire\Dinas\LaporanManager;
use App\Livewire\Dinas\Performa as DinasPerforma;
use App\Livewire\Dinas\PetaSebaran as DinasPetaSebaran;
use App\Livewire\Dinas\Profil as DinasProfil;
use App\Livewire\Eksekutif\EksekutifDashboard;
use App\Livewire\Eksekutif\PetaSebaran as EksekutifPetaSebaran;
use App\Livewire\Admin\PenarikanManager;
use App\Livewire\Admin\UmkmPenarikanManager;
use App\Livewire\BankSampah\Dashboard as BankSampahDashboard;
use App\Livewire\BankSampah\HargaView;
use App\Livewire\BankSampah\InfoBankSampah;
use App\Livewire\BankSampah\PetugasManager;
use App\Livewire\BankSampah\Profil;
use App\Livewire\BankSampah\RiwayatSetor;
use App\Livewire\Petugas\SetorSampah;
use App\Livewire\Tps\Dashboard as TpsDashboard;
use App\Livewire\Tps\InfoTps;
use App\Livewire\Tps\IuranManager;
use App\Livewire\Tps\MemberManager;
use App\Livewire\Umkm\Dashboard as UmkmDashboard;
use App\Livewire\Umkm\Keuangan as UmkmKeuangan;
use App\Livewire\Umkm\OrderManager;
use App\Livewire\Umkm\ProductManager;
use App\Livewire\Umkm\Profil as UmkmProfil;
use App\Livewire\Umkm\Rekomendasi as UmkmRekomendasi;
use App\Livewire\Umkm\Saldo as UmkmSaldo;
use App\Livewire\Auth\Login;
use App\Livewire\Public\ArtikelIndex;
use App\Livewire\Public\ArtikelShow;
use App\Livewire\Public\BankSampahIndex;
use App\Livewire\Public\BankSampahShow;
use App\Livewire\Public\Beranda;
use App\Livewire\Public\DaftarUmkm;
use App\Livewire\Public\LaporanIndex;
use App\Livewire\Public\LaporanShow;
use App\Livewire\Public\TpsIndex;
use App\Livewire\Public\TpsShow;
use App\Livewire\Public\UmkmIndex;
use App\Livewire\Public\UmkmShow;
use Illuminate\Support\Facades\Route;

Route::get('/', Beranda::class)->name('beranda');

// Pendaftaran UMKM mandiri (publik)
Route::get('/daftar-umkm', DaftarUmkm::class)->name('umkm.register');

// Edukasi / artikel (publik)
Route::get('/artikel', ArtikelIndex::class)->name('artikel.index');
Route::get('/artikel/{slug}', ArtikelShow::class)->name('artikel.show');

// Direktori publik (hanya lihat)
Route::get('/direktori/umkm', UmkmIndex::class)->name('publik.umkm.index');
Route::get('/direktori/umkm/{umkm}', UmkmShow::class)->name('publik.umkm.show');
Route::get('/direktori/tps', TpsIndex::class)->name('publik.tps.index');
Route::get('/direktori/tps/{tps}', TpsShow::class)->name('publik.tps.show');
Route::get('/direktori/bank-sampah', BankSampahIndex::class)->name('publik.bank-sampah.index');
Route::get('/direktori/bank-sampah/{bankSampah}', BankSampahShow::class)->name('publik.bank-sampah.show');
Route::get('/laporan', LaporanIndex::class)->name('publik.laporan.index');
Route::get('/laporan/{report}', LaporanShow::class)->name('publik.laporan.show');

/*
|--------------------------------------------------------------------------
| Login (satu komponen, beberapa route sesuai kelompok role)
|--------------------------------------------------------------------------
*/
$loginRoutes = [
    'login'             => '/login',
    'admin.login'       => '/admin/login',
    'dinas.login'       => '/dinas/login',
    'eksekutif.login'   => '/eksekutif/login',
    'umkm.login'        => '/umkm/login',
    'tps.login'         => '/tps/login',
    'bank-sampah.login' => '/bank-sampah/login',
];

foreach ($loginRoutes as $name => $path) {
    Route::get($path, Login::class)->middleware('guest')->name($name);
}

Route::post('/logout', LogoutController::class)->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Grup terproteksi per role
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    Route::middleware('role:super_admin|admin')->prefix('admin')->group(function () {
        Route::get('/', AdminDashboard::class)->name('admin.dashboard');

        // Wilayah
        Route::get('/wilayah/kecamatan', KecamatanManager::class)->name('admin.kecamatan');
        Route::get('/wilayah/kelurahan', KelurahanManager::class)->name('admin.kelurahan');
        Route::get('/wilayah/banjar', BanjarDinasManager::class)->name('admin.banjar');

        // Master data
        Route::get('/master/tps', TpsManager::class)->name('admin.tps');
        Route::get('/master/bank-sampah', BankSampahManager::class)->name('admin.bank-sampah');
        Route::get('/master/umkm', UmkmManager::class)->name('admin.umkm');
        Route::get('/master/harga-sampah', HargaSampahManager::class)->name('admin.harga-sampah');
        Route::get('/master/kategori-laporan', ReportCategoryManager::class)->name('admin.kategori-laporan');
        Route::get('/master/kategori-produk', ProductCategoryManager::class)->name('admin.kategori-produk');

        // Pengawasan
        Route::get('/laporan', ReportManager::class)->name('admin.laporan');
        Route::get('/produk', AdminProductManager::class)->name('admin.produk');

        // Sistem
        Route::get('/pengguna', UserManager::class)->name('admin.pengguna');
        Route::get('/penarikan', PenarikanManager::class)->name('admin.penarikan');
        Route::get('/penarikan-umkm', UmkmPenarikanManager::class)->name('admin.penarikan-umkm');
        Route::get('/artikel', ArticleManager::class)->name('admin.artikel');
        Route::get('/profil', AdminProfil::class)->name('admin.profil');
    });

    Route::middleware('role:admin_dinas')->prefix('dinas')->group(function () {
        Route::get('/', DinasDashboard::class)->name('dinas.dashboard');
        Route::get('/performa', DinasPerforma::class)->name('dinas.performa');
        Route::get('/peta', DinasPetaSebaran::class)->name('dinas.peta');
        Route::get('/laporan', LaporanManager::class)->name('dinas.laporan');
        Route::get('/profil', DinasProfil::class)->name('dinas.profil');
    });

    Route::middleware('role:bupati|camat|lurah|kepala_dinas_banjar')->prefix('eksekutif')->group(function () {
        Route::get('/', EksekutifDashboard::class)->name('eksekutif.dashboard');
        Route::get('/peta', EksekutifPetaSebaran::class)->name('eksekutif.peta');
    });

    Route::middleware('role:umkm')->prefix('umkm')->group(function () {
        Route::get('/', UmkmDashboard::class)->name('umkm.dashboard');
        Route::get('/produk', ProductManager::class)->name('umkm.produk');
        Route::get('/pesanan', OrderManager::class)->name('umkm.pesanan');
        Route::get('/keuangan', UmkmKeuangan::class)->name('umkm.keuangan');
        Route::get('/saldo', UmkmSaldo::class)->name('umkm.saldo');
        Route::get('/rekomendasi', UmkmRekomendasi::class)->name('umkm.rekomendasi');
        Route::get('/profil', UmkmProfil::class)->name('umkm.profil');
    });

    Route::middleware('role:admin_tps')->prefix('tps')->group(function () {
        Route::get('/', TpsDashboard::class)->name('tps.dashboard');
        Route::get('/nasabah', MemberManager::class)->name('tps.nasabah');
        Route::get('/iuran', IuranManager::class)->name('tps.iuran');
        Route::get('/info', InfoTps::class)->name('tps.info');
    });

    Route::middleware('role:admin_bank_sampah|petugas_bank_sampah')->prefix('bank-sampah')->group(function () {
        Route::get('/', BankSampahDashboard::class)->name('bank-sampah.dashboard');
        Route::get('/riwayat', RiwayatSetor::class)->name('bank-sampah.riwayat');
        Route::get('/harga', HargaView::class)->name('bank-sampah.harga');
        Route::get('/profil', Profil::class)->name('bank-sampah.profil');

        Route::get('/setor', SetorSampah::class)->middleware('role:petugas_bank_sampah')->name('bank-sampah.setor');

        Route::middleware('role:admin_bank_sampah')->group(function () {
            Route::get('/petugas', PetugasManager::class)->name('bank-sampah.petugas');
            Route::get('/info', InfoBankSampah::class)->name('bank-sampah.info');
        });
    });
});