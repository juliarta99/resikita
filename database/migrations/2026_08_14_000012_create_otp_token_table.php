<?php

declare(strict_types=1);

use App\Enums\TujuanOtp;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kode sekali pakai untuk verifikasi email, verifikasi nomor WhatsApp,
 * dan pengaturan ulang kata sandi.
 *
 * Skema Niti Resik punya dua mekanisme berdampingan, `otp_tokens` dan
 * `wa_verifications`, dengan alur yang mirip tapi tidak identik.
 * Yang kedua dibuang; tabel ini melayani ketiga keperluan.
 *
 * Kode disimpan sebagai hash, bukan teks terbuka. OTP adalah kredensial
 * sementara, jadi diperlakukan seperti kata sandi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_token', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->enum('tujuan', TujuanOtp::values());
            $table->string('kode_hash');

            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('used_at')->nullable();

            $table->timestamps();

            // Pencarian kode aktif milik seorang pengguna untuk satu tujuan.
            $table->index(['user_id', 'tujuan', 'used_at']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_token');
    }
};
