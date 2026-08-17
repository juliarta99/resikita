<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tindak lanjut Fasilitator Wilayah ke dinas terkait.
 *
 * Ini yang membuat laporan dari wilayah belum terjangkau tidak berakhir
 * sebagai data mati. Fasilitator mengontak dinas di luar sistem, lalu
 * mencatat hasilnya di sini. Rekaman itu sekaligus menjadi bukti
 * pendampingan saat wilayah tersebut diajak mendaftar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laporan_tindak_lanjut', function (Blueprint $table) {
            $table->id();

            $table->foreignId('laporan_id')
                ->constrained('laporan')
                ->cascadeOnDelete();

            $table->foreignId('fasilitator_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('nama_dinas', 191);
            $table->string('kontak_dinas', 191)->nullable();
            $table->date('tanggal_kontak');
            $table->text('hasil');
            $table->string('lampiran_path')->nullable();

            $table->timestamps();

            $table->index(['laporan_id', 'tanggal_kontak']);
            $table->index('fasilitator_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_tindak_lanjut');
    }
};
