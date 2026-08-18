<?php

declare(strict_types=1);

use App\Enums\MetodeBayar;
use App\Enums\StatusIuran;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tagihan iuran TPS per periode bulanan.
 *
 * Iuran bisa dibayar dari saldo dompet, saldo yang asalnya dari
 * setoran sampah. Warga yang rajin memilah bisa menutup retribusinya
 * sendiri, dan itu titik paling nyata dari klaim ekonomi sirkular.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tps_iuran', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tps_anggota_id')
                ->constrained('tps_anggota')
                ->cascadeOnDelete();

            // Format YYYY-MM.
            $table->char('periode', 7);

            // Rupiah penuh sebagai integer.
            $table->bigInteger('jumlah');

            $table->enum('status', StatusIuran::values())
                ->default(StatusIuran::Menunggu->value);

            $table->enum('metode_bayar', MetodeBayar::values())->nullable();
            $table->timestamp('dibayar_at')->nullable();

            $table->timestamps();

            // Satu anggota satu tagihan per periode.
            $table->unique(['tps_anggota_id', 'periode']);
            $table->index(['periode', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tps_iuran');
    }
};
