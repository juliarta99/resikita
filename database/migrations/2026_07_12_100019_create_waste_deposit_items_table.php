<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waste_deposit_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deposit_id')->constrained('waste_deposits')->cascadeOnDelete();
            $table->foreignId('waste_price_id')->constrained('waste_prices');
            $table->decimal('berat', 10, 2);
            $table->decimal('harga_snapshot', 12, 2);
            $table->decimal('subtotal', 14, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waste_deposit_items');
    }
};
