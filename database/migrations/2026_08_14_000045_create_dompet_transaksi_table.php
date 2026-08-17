<?php

declare(strict_types=1);

use App\Enums\TipeTransaksiDompet;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mutasi saldo dompet.
 *
 * `saldo_sebelum` dan `saldo_sesudah` disimpan pada tiap baris, bukan
 * dihitung ulang dari penjumlahan. Dengan begitu selisih saldo bisa
 * diaudit per transaksi, dan satu baris rusak tidak menular ke seluruh
 * riwayat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dompet_transaksi', function (Blueprint $table) {
            $table->id();

            $table->foreignId('dompet_id')
                ->constrained('dompet')
                ->cascadeOnDelete();

            $table->enum('tipe', TipeTransaksiDompet::values());

            // Selalu positif. Arah penambahan/pengurangan ditentukan oleh
            // `tipe`, lihat TipeTransaksiDompet::arah().
            $table->bigInteger('jumlah');
            $table->bigInteger('saldo_sebelum');
            $table->bigInteger('saldo_sesudah');

            // Rujukan bebas ke sumber transaksi: setoran, pesanan,
            // penarikan, atau iuran.
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->string('keterangan', 191)->nullable();

            $table->timestamps();

            $table->index(['dompet_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('tipe');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dompet_transaksi');
    }
};
