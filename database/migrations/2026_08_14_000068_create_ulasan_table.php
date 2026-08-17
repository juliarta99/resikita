<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ulasan pembeli.
 *
 * Terikat ke `pesanan_id` supaya hanya pembeli yang benar-benar
 * bertransaksi bisa mengulas. `produk_id` dan `umkm_id` disimpan agar
 * rata-rata rating bisa dihitung dengan withAvg() tanpa join berlapis.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ulasan', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('pesanan_id')
                ->constrained('pesanan')
                ->cascadeOnDelete();

            $table->foreignId('produk_id')
                ->nullable()
                ->constrained('produk')
                ->nullOnDelete();

            $table->foreignId('umkm_id')
                ->nullable()
                ->constrained('umkm')
                ->nullOnDelete();

            $table->unsignedTinyInteger('rating');
            $table->text('komentar')->nullable();
            $table->string('foto_path')->nullable();

            $table->timestamps();

            // Satu ulasan per produk per pesanan.
            $table->unique(['pesanan_id', 'produk_id']);
            $table->index(['produk_id', 'rating']);
            $table->index(['umkm_id', 'rating']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ulasan');
    }
};
