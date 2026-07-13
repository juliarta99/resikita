<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('foto')->nullable();
            $table->string('hasil_jenis');
            $table->text('deskripsi')->nullable();
            $table->string('kategori');                 // organik|anorganik|b3|residu|tidak_yakin
            $table->string('material')->nullable();      // Plastik|Kertas|Logam|Kaca|Makanan|Elektronik|...
            $table->decimal('confidence', 5, 2)->default(0);   // 0..1
            $table->boolean('dapat_didaur_ulang')->default(false);
            $table->decimal('nilai_jual', 10, 2)->default(0);  // Rp / kg
            $table->decimal('estimasi_berat', 8, 3)->default(0); // kg
            $table->json('langkah_pengolahan')->nullable();
            $table->text('rekomendasi')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classifications');
    }
};
