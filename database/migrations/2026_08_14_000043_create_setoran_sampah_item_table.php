<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rincian per jenis sampah dalam satu setoran.
 *
 * `jenis_snapshot` dan `harga_snapshot` menyimpan keadaan katalog pada
 * saat transaksi terjadi. Bank sampah mengubah harga cukup sering;
 * tanpa snapshot, mengubah harga hari ini akan diam-diam mengubah nilai
 * setoran tahun lalu, dan riwayat nasabah berhenti bisa dipercaya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('setoran_sampah_item', function (Blueprint $table) {
            $table->id();

            $table->foreignId('setoran_id')
                ->constrained('setoran_sampah')
                ->cascadeOnDelete();

            $table->foreignId('harga_id')
                ->nullable()
                ->constrained('bank_sampah_harga')
                ->nullOnDelete();

            $table->string('jenis_snapshot', 150);
            $table->decimal('berat', 10, 2);
            $table->bigInteger('harga_snapshot');
            $table->bigInteger('subtotal');

            $table->timestamps();

            $table->index('setoran_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('setoran_sampah_item');
    }
};
