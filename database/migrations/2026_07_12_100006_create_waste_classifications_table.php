<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waste_classifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('image_path');
            $table->string('hasil_jenis')->nullable();
            $table->string('kategori')->nullable();
            $table->decimal('confidence', 4, 3)->nullable();
            $table->json('langkah_pengolahan')->nullable();
            $table->text('rekomendasi_daur_ulang')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waste_classifications');
    }
};
