<?php

declare(strict_types=1);

use App\Enums\JenisKelamin;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel pengguna Resikita.
 *
 * Perhatikan yang TIDAK ada di sini: `nik` dan `nip`. Keduanya dihapus
 * dari skema Niti Resik atas alasan yang dijabarkan di CLAUDE.md 4.2,
 * NIK adalah data pribadi spesifik menurut UU No. 27/2022 dan tidak satu
 * pun alur bisnis Resikita membutuhkannya. Jangan menambahkannya kembali.
 *
 * Kunci asing ke `wilayah`, `bank_sampah`, dan `umkm` ditambahkan di
 * migration terakhir, karena tabel tujuannya baru dibuat setelah ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->string('name', 150);

            // Identitas utama. Wajib dan unik, pengganti peran NIK di skema lama.
            $table->string('email', 191)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            // Opsional, hanya untuk notifikasi WhatsApp lewat Fonnte.
            $table->string('phone', 20)->nullable();
            $table->timestamp('phone_verified_at')->nullable();

            $table->string('avatar_path')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->enum('jenis_kelamin', JenisKelamin::values())->nullable();

            // ULID acak untuk QR nasabah bank sampah. Sengaja tidak berasal
            // dari data pribadi apa pun, sehingga kode yang bocor tidak
            // membocorkan identitas pemiliknya.
            $table->char('kode_qr', 26)->nullable()->unique();

            // Domisili untuk masyarakat; cakupan kewenangan untuk role
            // pemerintahan dan petugas. Satu kolom menggantikan empat
            // kolom wilayah bertumpuk di skema lama.
            $table->foreignId('wilayah_id')->nullable();
            $table->foreignId('bank_sampah_id')->nullable();
            $table->foreignId('umkm_id')->nullable();

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->boolean('is_active')->default(true);

            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index('wilayah_id');
            $table->index('bank_sampah_id');
            $table->index('umkm_id');
            $table->index('is_active');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email', 191)->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
