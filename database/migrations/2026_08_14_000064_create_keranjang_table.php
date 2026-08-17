<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('keranjang', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('produk_id')
                ->constrained('produk')
                ->cascadeOnDelete();

            $table->unsignedInteger('qty')->default(1);

            $table->timestamps();

            // Satu baris per produk per pengguna; menambah item yang sama
            // menaikkan qty, bukan membuat baris baru.
            $table->unique(['user_id', 'produk_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('keranjang');
    }
};
