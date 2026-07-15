<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('umkm_id')->constrained('umkm');
            $table->decimal('total', 14, 2);
            $table->decimal('ongkir', 12, 2)->default(0);
            $table->enum('metode_bayar', ['saldo', 'midtrans']);
            $table->enum('status', ['menunggu_bayar', 'dibayar', 'dikemas', 'dikirim', 'selesai', 'dibatalkan'])->default('menunggu_bayar');
            $table->string('alamat_kirim');
            $table->integer('destination_id')->nullable();
            $table->string('kurir')->nullable();
            $table->string('no_resi')->nullable();
            $table->text('snap_token')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
