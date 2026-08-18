<?php

declare(strict_types=1);

use App\Enums\KategoriSampah;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Katalog harga sampah per bank sampah.
 *
 * Di skema lama tabel ini bernama `waste_prices` dan tidak punya
 * `bank_sampah_id`, sehingga harga bersifat global untuk seluruh
 * aplikasi. Itu keliru secara domain: harga sampah berbeda antar unit
 * dan antar daerah, bergantung pada pengepul yang tersedia. Sekarang
 * harga dimiliki oleh unitnya sendiri.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_sampah_harga', function (Blueprint $table) {
            $table->id();

            $table->foreignId('bank_sampah_id')
                ->constrained('bank_sampah')
                ->cascadeOnDelete();

            $table->string('jenis_sampah', 150);
            $table->enum('kategori', KategoriSampah::values());
            $table->string('satuan', 20)->default('kg');

            // Rupiah penuh sebagai integer.
            $table->bigInteger('harga_per_satuan');

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['bank_sampah_id', 'jenis_sampah']);
            $table->index(['bank_sampah_id', 'is_active']);
            $table->index('kategori');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_sampah_harga');
    }
};
