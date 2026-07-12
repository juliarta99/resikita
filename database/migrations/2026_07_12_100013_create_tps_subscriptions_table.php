<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tps_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tps_member_id')->constrained('tps_members')->cascadeOnDelete();
            $table->string('periode');
            $table->decimal('jumlah', 12, 2);
            $table->enum('status', ['menunggu', 'lunas', 'gagal'])->default('menunggu');
            $table->enum('metode_bayar', ['saldo', 'midtrans'])->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tps_subscriptions');
    }
};
