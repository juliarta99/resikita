<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Foto produk.
 *
 * Ini foto asli milik UMKM. Asisten Konten boleh menyusun sampul
 * berbasis template di atasnya, tapi tidak boleh menggantinya dengan
 * citra hasil generate, lihat CLAUDE.md 10.3. Hasil komposisi disimpan
 * terpisah di `konten_promosi.sampul_path`, bukan menimpa baris ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produk_foto', function (Blueprint $table) {
            $table->id();

            $table->foreignId('produk_id')
                ->constrained('produk')
                ->cascadeOnDelete();

            $table->string('path');
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->boolean('is_utama')->default(false);

            $table->timestamps();

            $table->index(['produk_id', 'urutan']);
            $table->index(['produk_id', 'is_utama']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produk_foto');
    }
};
