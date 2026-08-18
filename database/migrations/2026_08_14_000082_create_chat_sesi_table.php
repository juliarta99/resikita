<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sesi percakapan dengan asisten literasi lingkungan.
 *
 * Kolom `messages` bertipe longtext dari skema lama DIBUANG. Dulu
 * riwayat disimpan dua kali, sekali sebagai JSON di kolom itu, sekali
 * lagi sebagai baris di tabel pesan, sehingga keduanya rutin tidak
 * sinkron. Sekarang hanya tabel relasional yang menjadi sumber kebenaran.
 *
 * `wilayah_konteks_id` menyimpan wilayah yang dipakai sebagai konteks
 * lokal sesi ini. Nama daerah tidak pernah ditanam di prompt dasar;
 * konteks disisipkan per sesi dari kolom ini (CLAUDE.md 10.2).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_sesi', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('judul', 191)->default('Percakapan Baru');

            $table->foreignId('wilayah_konteks_id')
                ->nullable()
                ->constrained('wilayah')
                ->nullOnDelete();

            $table->timestamp('terakhir_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'terakhir_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_sesi');
    }
};
