<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waste_prices', function (Blueprint $table) {
            $table->id();
            $table->string('jenis_sampah');
            $table->string('satuan')->default('kg');
            $table->decimal('harga_per_kg', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waste_prices');
    }
};
