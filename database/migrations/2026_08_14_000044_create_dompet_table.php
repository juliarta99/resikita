<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dompet saldo masyarakat.
 *
 * Saldo `bigint` rupiah, bukan decimal(14,2) seperti skema lama.
 * Uang yang disimpan sebagai desimal akan mengumpulkan galat
 * pembulatan pada operasi berulang, dan saldo bank sampah justru
 * jenis nilai yang paling sering ditambah-kurang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dompet', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            $table->bigInteger('saldo')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dompet');
    }
};
