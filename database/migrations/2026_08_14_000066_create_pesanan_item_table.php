<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rincian item pesanan.
 *
 * `nama_snapshot` dan `harga_snapshot` mengunci keadaan produk pada saat
 * checkout. UMKM boleh mengubah harga atau mengganti nama produknya
 * kapan pun; nota yang sudah terbit tidak boleh berubah karenanya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pesanan_item', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pesanan_id')
                ->constrained('pesanan')
                ->cascadeOnDelete();

            $table->foreignId('produk_id')
                ->nullable()
                ->constrained('produk')
                ->nullOnDelete();

            $table->string('nama_snapshot', 191);
            $table->bigInteger('harga_snapshot');
            $table->unsignedInteger('qty');
            $table->bigInteger('subtotal');

            $table->timestamps();

            $table->index('pesanan_id');
            $table->index('produk_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesanan_item');
    }
};
