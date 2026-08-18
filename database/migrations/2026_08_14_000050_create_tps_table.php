<?php

declare(strict_types=1);

use App\Enums\JenisTps;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tempat penampungan sementara.
 *
 * TPS tetap ada sebagai entitas, tapi role `admin_tps` dihapus dari
 * skema role, TPS kini dikelola oleh pemerintah wilayah yang
 * membawahinya (CLAUDE.md 6.1).
 *
 * `jenis` membedakan TPS biasa dari TPS3R. Pembedaan itu penting untuk
 * literasi: TPS hanya memindahkan sampah, TPS3R mengolah sebagiannya di
 * tempat, sehingga warga tahu ke mana sampah terpilahnya berguna.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tps', function (Blueprint $table) {
            $table->id();

            $table->string('nama', 191);
            $table->string('alamat')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('foto')->nullable();

            $table->enum('jenis', JenisTps::values())
                ->default(JenisTps::Tps->value);

            $table->boolean('is_berbayar')->default(false);

            // Rupiah penuh sebagai integer.
            $table->bigInteger('tarif_bulanan')->nullable();

            // Menggantikan `banjar_id` di skema lama.
            $table->foreignId('wilayah_id')
                ->nullable()
                ->constrained('wilayah')
                ->nullOnDelete();

            $table->decimal('kapasitas_ton', 10, 2)->nullable();

            $table->timestamps();

            $table->index(['wilayah_id', 'jenis']);
            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tps');
    }
};
