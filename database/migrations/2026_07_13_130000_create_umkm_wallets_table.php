<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('umkm_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('umkm_id')->constrained('umkm')->cascadeOnDelete();
            $table->decimal('saldo', 15, 2)->default(0);
            $table->timestamps();
            $table->unique('umkm_id');
        });

        Schema::create('umkm_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('umkm_wallet_id')->constrained('umkm_wallets')->cascadeOnDelete();
            $table->string('tipe');                 // penjualan / penarikan / refund / penyesuaian
            $table->decimal('jumlah', 15, 2);       // + masuk, - keluar
            $table->decimal('saldo_after', 15, 2);
            $table->nullableMorphs('reference');
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('umkm_wallet_transactions');
        Schema::dropIfExists('umkm_wallets');
    }
};
