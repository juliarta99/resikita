<?php

declare(strict_types=1);

use App\Enums\StatusProgres;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catatan progres dari lapangan.
 *
 * Koordinat ikut disimpan supaya foto bukti bisa dipadankan dengan
 * lokasi laporan, bukti penanganan yang diambil dari tempat lain
 * bisa terdeteksi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laporan_progres', function (Blueprint $table) {
            $table->id();

            $table->foreignId('laporan_id')
                ->constrained('laporan')
                ->cascadeOnDelete();

            $table->foreignId('petugas_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->text('catatan')->nullable();
            $table->string('foto_bukti')->nullable();

            $table->enum('status_progres', StatusProgres::values());

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->timestamps();

            $table->index(['laporan_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_progres');
    }
};
