<?php

declare(strict_types=1);

use App\Enums\StatusRegistrasiWilayah;
use App\Enums\TingkatWilayah;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hierarki wilayah administrasi nasional.
 *
 * Menggantikan tiga tabel datar khas Bali di skema Niti Resik
 * (`kecamatan`, `kelurahan`, `banjar_dinas`) dengan satu tabel
 * berjenjang empat tingkat: provinsi → kabupaten → kecamatan → desa.
 *
 * `kode` mengikuti kode wilayah administrasi Kemendagri, bukan
 * penomoran sendiri. Itulah yang memungkinkan data Resikita
 * dipadankan dengan data pemerintah dan diperluas ke seluruh
 * Indonesia tanpa migrasi besar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wilayah', function (Blueprint $table) {
            $table->id();

            $table->string('kode', 20)->unique();
            $table->string('nama', 150);
            $table->enum('tingkat', TingkatWilayah::values());

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('wilayah')
                ->nullOnDelete();

            // Titik pusat wilayah, dipakai sebagai jangkar peta dan
            // sebagai cadangan resolusi lokasi saat titik laporan tidak
            // jatuh di dalam wilayah mana pun yang terdaftar.
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->enum('status_registrasi', StatusRegistrasiWilayah::values())
                ->default(StatusRegistrasiWilayah::BelumTerjangkau->value);

            // Naik setiap kali ada laporan dari wilayah yang belum
            // terjangkau. Fasilitator memakai angka ini untuk memutuskan
            // wilayah mana yang diajak bergabung lebih dulu.
            $table->integer('skor_prioritas')->default(0);

            $table->timestamp('terverifikasi_at')->nullable();
            $table->timestamps();

            $table->index('tingkat');
            $table->index('status_registrasi');
            $table->index(['tingkat', 'status_registrasi']);
            $table->index(['parent_id', 'tingkat']);
            $table->index('skor_prioritas');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wilayah');
    }
};
