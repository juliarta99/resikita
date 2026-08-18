<?php

declare(strict_types=1);

use App\Enums\StatusSetoran;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('setoran_sampah', function (Blueprint $table) {
            $table->id();

            $table->foreignId('bank_sampah_id')
                ->constrained('bank_sampah')
                ->restrictOnDelete();

            // Pengguna bank sampah yang melayani penimbangan.
            $table->foreignId('petugas_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('nasabah_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('kode_setoran', 30)->unique();

            $table->decimal('total_berat', 10, 2)->default(0);

            // Rupiah penuh sebagai integer.
            $table->bigInteger('total_nilai')->default(0);

            $table->enum('status', StatusSetoran::values())
                ->default(StatusSetoran::Proses->value);

            $table->text('catatan')->nullable();

            $table->timestamps();

            $table->index(['bank_sampah_id', 'status']);
            $table->index(['nasabah_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('setoran_sampah');
    }
};
