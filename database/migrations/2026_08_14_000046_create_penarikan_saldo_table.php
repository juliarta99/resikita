<?php

declare(strict_types=1);

use App\Enums\StatusPenarikan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penarikan_saldo', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // Rupiah penuh sebagai integer.
            $table->bigInteger('jumlah');

            $table->string('metode', 50)->default('transfer_bank');
            $table->string('nama_bank', 100)->nullable();
            $table->string('no_rekening', 50);
            $table->string('atas_nama', 150);

            $table->enum('status', StatusPenarikan::values())
                ->default(StatusPenarikan::Menunggu->value);

            $table->foreignId('disetujui_oleh')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('catatan')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penarikan_saldo');
    }
};
