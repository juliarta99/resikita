<?php

use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BankSampahController;
use App\Http\Controllers\Api\ChatbotController;
use App\Http\Controllers\Api\ChatConversationController;
use App\Http\Controllers\Api\ClassificationController;
use App\Http\Controllers\Api\DirektoriController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PetugasController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ShippingController;
use App\Http\Controllers\Api\WalletController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ---------- Autentikasi (publik) ----------
    Route::post('register', [AuthController::class, 'register']);
    Route::post('register/verify', [AuthController::class, 'verifyRegister']);
    Route::post('register/resend', [AuthController::class, 'resendRegisterOtp']);
    Route::post('login', [AuthController::class, 'login']);

    // Callback Midtrans (server-to-server, tanpa auth)
    Route::post('midtrans/notification', [OrderController::class, 'notifikasiMidtrans']);

    // ---------- Browse publik (guest boleh lihat, tidak boleh aksi) ----------
    Route::get('artikel', [ArticleController::class, 'index']);
    Route::get('artikel/{slug}', [ArticleController::class, 'show']);
    Route::get('produk', [ProductController::class, 'index']);
    Route::get('produk/{product}', [ProductController::class, 'show']);
    Route::get('direktori/tps', [DirektoriController::class, 'tps']);
    Route::get('direktori/tps/{tps}', [DirektoriController::class, 'tpsDetail']);
    Route::get('direktori/bank-sampah', [DirektoriController::class, 'bankSampah']);
    Route::get('direktori/bank-sampah/{bankSampah}', [DirektoriController::class, 'bankSampahDetail']);
    Route::get('direktori/umkm', [DirektoriController::class, 'umkm']);
    Route::get('direktori/umkm/{umkm}', [DirektoriController::class, 'umkmDetail']);
    Route::get('harga-sampah', [DirektoriController::class, 'hargaSampah']);
    Route::get('peta/laporan', [DirektoriController::class, 'petaLaporan']);

    // ---------- Perlu token (masyarakat) ----------
    Route::middleware('auth:sanctum')->group(function () {

        // Akun
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::put('profil', [ProfileController::class, 'update']);
        Route::put('profil/password', [ProfileController::class, 'updatePassword']);

        // Saldo & bank sampah digital
        Route::get('saldo', [WalletController::class, 'saldo']);
        Route::get('transaksi', [WalletController::class, 'transaksi']);
        Route::get('setoran', [WalletController::class, 'setoran']);
        Route::post('penarikan', [WalletController::class, 'ajukanPenarikan']);
        Route::get('penarikan', [WalletController::class, 'penarikan']);

        // Direktori (aksi)
        Route::get('direktori/tps-saya', [DirektoriController::class, 'tpsSaya']);
        Route::post('direktori/tps/{tps}/gabung', [DirektoriController::class, 'gabungTps']);

        // Ongkir
        Route::get('ongkir/tujuan', [ShippingController::class, 'cariTujuan']);
        Route::post('ongkir/hitung', [ShippingController::class, 'hitung']);

        // Pesanan
        Route::get('pesanan', [OrderController::class, 'index']);
        Route::post('pesanan', [OrderController::class, 'store']);
        Route::get('pesanan/{order}', [OrderController::class, 'show']);
        Route::post('pesanan/{order}/batal', [OrderController::class, 'cancel']);

        // Laporan
        Route::get('laporan/kategori', [ReportController::class, 'kategori']);
        Route::get('laporan', [ReportController::class, 'index']);
        Route::post('laporan', [ReportController::class, 'store']);
        Route::get('laporan/{report}', [ReportController::class, 'show']);

        // AI
        Route::post('klasifikasi', [ClassificationController::class, 'store']);
        Route::get('klasifikasi', [ClassificationController::class, 'index']);
        Route::get('klasifikasi/{classification}', [ClassificationController::class, 'show']);
        Route::delete('klasifikasi/{classification}', [ClassificationController::class, 'destroy']);

        Route::post('chatbot', ChatbotController::class);
        Route::get('chatbot/riwayat', [ChatConversationController::class, 'index']);
        Route::get('chatbot/riwayat/{conversation}', [ChatConversationController::class, 'show']);
        Route::patch('chatbot/riwayat/{conversation}', [ChatConversationController::class, 'update']);
        Route::delete('chatbot/riwayat/{conversation}', [ChatConversationController::class, 'destroy']);
    });

    // ---------- Petugas Lapangan ----------
    Route::middleware(['auth:sanctum', 'role:petugas_lapangan'])->prefix('petugas')->group(function () {
        Route::get('penugasan', [PetugasController::class, 'penugasan']);
        Route::get('penugasan/{report}', [PetugasController::class, 'detail']);
        Route::post('penugasan/{report}/progress', [PetugasController::class, 'progress']);
        Route::post('penugasan/{report}/selesai', [PetugasController::class, 'selesai']);
    });

    // ---------- Petugas Bank Sampah ----------
    Route::middleware(['auth:sanctum', 'role:petugas_bank_sampah'])->prefix('bank-sampah')->group(function () {
        Route::get('harga', [BankSampahController::class, 'harga']);
        Route::get('nasabah', [BankSampahController::class, 'cariNasabah']);
        Route::post('setor', [BankSampahController::class, 'setor']);
        Route::get('riwayat', [BankSampahController::class, 'riwayat']);
    });
});