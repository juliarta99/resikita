<?php

declare(strict_types=1);

use App\Enums\StatusAktif;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tps_anggota', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tps_id')
                ->constrained('tps')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->enum('status', StatusAktif::values())
                ->default(StatusAktif::Aktif->value);

            $table->timestamp('bergabung_at');

            $table->timestamps();

            // Satu warga hanya boleh terdaftar sekali di satu TPS.
            $table->unique(['tps_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tps_anggota');
    }
};
