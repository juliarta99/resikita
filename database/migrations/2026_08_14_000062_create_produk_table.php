<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Produk daur ulang yang dijual UMKM.
 *
 * `stok` adalah jumlah unit tersedia, integer biasa. Skema lama memakai
 * konvensi warisan yang membingungkan (0 berarti tersedia, NULL berarti
 * dipesan, 1 berarti terjual); konvensi itu tidak dibawa ke Resikita.
 *
 * `bahan_baku` bukan kolom hiasan. Produk di marketplace ini dijual
 * karena asalnya dari sampah terpilah, jadi asal bahan adalah bagian
 * dari nilai jualnya dan layak ditampilkan ke pembeli.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produk', function (Blueprint $table) {
            $table->id();

            $table->foreignId('umkm_id')
                ->constrained('umkm')
                ->cascadeOnDelete();

            $table->foreignId('kategori_id')
                ->constrained('produk_kategori')
                ->restrictOnDelete();

            $table->string('nama', 191);
            $table->string('slug', 191);
            $table->text('deskripsi')->nullable();

            // Rupiah penuh sebagai integer.
            $table->bigInteger('harga');

            $table->unsignedInteger('stok')->default(0);
            $table->unsignedInteger('berat_gram');

            $table->string('bahan_baku', 191)->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['umkm_id', 'slug']);
            $table->index(['umkm_id', 'is_active']);
            $table->index(['kategori_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produk');
    }
};
