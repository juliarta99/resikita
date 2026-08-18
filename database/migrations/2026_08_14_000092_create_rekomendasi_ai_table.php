<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rekomendasi tindakan hasil analisis AI untuk pemerintah wilayah.
 *
 * `scope_type` dan `scope_id` menyebut untuk siapa rekomendasi dibuat,
 * sebuah wilayah, sebuah UMKM, atau seluruh platform.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekomendasi_ai', function (Blueprint $table) {
            $table->id();

            $table->string('scope_type', 100);
            $table->unsignedBigInteger('scope_id')->nullable();

            // Periode yang dianalisis, format YYYY-MM.
            $table->string('periode', 20);

            $table->longText('konten');
            $table->json('raw_response')->nullable();

            $table->foreignId('dibuat_oleh')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['scope_type', 'scope_id', 'periode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekomendasi_ai');
    }
};
