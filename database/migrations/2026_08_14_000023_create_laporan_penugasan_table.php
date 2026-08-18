<?php

declare(strict_types=1);

use App\Enums\StatusPenugasan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penugasan petugas atas sebuah laporan.
 *
 * Dibuat oleh penanggung jawab wilayah. Tabel ini juga yang membatasi
 * cakupan petugas: WilayahScopeService menyaring laporan untuk role
 * `petugas` lewat keberadaan baris di sini, bukan lewat wilayah_id-nya
 * (CLAUDE.md 9.5).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laporan_penugasan', function (Blueprint $table) {
            $table->id();

            $table->foreignId('laporan_id')
                ->constrained('laporan')
                ->cascadeOnDelete();

            $table->foreignId('petugas_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('ditugaskan_oleh')
                ->constrained('users')
                ->restrictOnDelete();

            $table->enum('status', StatusPenugasan::values())
                ->default(StatusPenugasan::Ditugaskan->value);

            $table->timestamp('ditugaskan_at');
            $table->text('catatan')->nullable();

            $table->timestamps();

            $table->index(['petugas_id', 'status']);
            $table->index(['laporan_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_penugasan');
    }
};
