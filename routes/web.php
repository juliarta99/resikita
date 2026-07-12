<?php

use App\Http\Controllers\Auth\LogoutController;
use App\Livewire\Admin\KecamatanManager;
use App\Livewire\Auth\Login;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

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
| Grup terproteksi per role (+ dashboard placeholder)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    Route::middleware('role:super_admin|admin')->prefix('admin')->group(function () {
        Route::view('/', 'dashboard')->name('admin.dashboard');
        Route::get('/wilayah/kecamatan', KecamatanManager::class)->name('admin.kecamatan');
    });

    Route::middleware('role:admin_dinas')->prefix('dinas')->group(function () {
        Route::view('/', 'dashboard')->name('dinas.dashboard');
    });

    Route::middleware('role:bupati|camat|lurah|kepala_dinas_banjar')->prefix('eksekutif')->group(function () {
        Route::view('/', 'dashboard')->name('eksekutif.dashboard');
    });

    Route::middleware('role:umkm')->prefix('umkm')->group(function () {
        Route::view('/', 'dashboard')->name('umkm.dashboard');
    });

    Route::middleware('role:admin_tps')->prefix('tps')->group(function () {
        Route::view('/', 'dashboard')->name('tps.dashboard');
    });

    Route::middleware('role:admin_bank_sampah|petugas_bank_sampah')->prefix('bank-sampah')->group(function () {
        Route::view('/', 'dashboard')->name('bank-sampah.dashboard');
    });
});
