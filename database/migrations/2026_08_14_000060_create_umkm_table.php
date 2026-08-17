<?php

declare(strict_types=1);

use App\Enums\StatusUmkm;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('umkm', function (Blueprint $table) {
            $table->id();

            $table->string('nama', 191);
            $table->text('deskripsi')->nullable();
            $table->string('alamat')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 191)->nullable();
            $table->string('foto')->nullable();

            // Menggantikan `banjar_id` di skema lama.
            $table->foreignId('wilayah_id')
                ->nullable()
                ->constrained('wilayah')
                ->nullOnDelete();

            $table->enum('status', StatusUmkm::values())
                ->default(StatusUmkm::Menunggu->value);
            $table->boolean('is_verified')->default(false);

            $table->timestamps();

            $table->index(['wilayah_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('umkm');
    }
};
