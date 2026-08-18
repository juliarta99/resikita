<?php

declare(strict_types=1);

use App\Enums\StatusPembayaran;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pembayaran, polimorfik.
 *
 * Satu tabel melayani pesanan marketplace maupun iuran TPS, karena
 * keduanya memakai jalur Midtrans yang sama. `raw_payload` menyimpan
 * callback mentah, ketika ada sengketa pembayaran, isi payload dari
 * penyedia adalah bukti yang tidak bisa direkonstruksi ulang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id();

            $table->string('payable_type', 100);
            $table->unsignedBigInteger('payable_id');

            $table->string('metode', 50);

            $table->string('midtrans_order_id', 100)->nullable();
            $table->string('midtrans_transaction_id', 100)->nullable();

            // Rupiah penuh sebagai integer.
            $table->bigInteger('jumlah');

            $table->enum('status', StatusPembayaran::values())
                ->default(StatusPembayaran::Pending->value);

            $table->timestamp('dibayar_at')->nullable();
            $table->json('raw_payload')->nullable();

            $table->timestamps();

            $table->index(['payable_type', 'payable_id']);
            $table->index('midtrans_order_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran');
    }
};
