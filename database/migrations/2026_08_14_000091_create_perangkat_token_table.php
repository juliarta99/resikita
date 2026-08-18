<?php

declare(strict_types=1);

use App\Enums\PlatformPerangkat;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Token perangkat untuk notifikasi dorong. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perangkat_token', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('token', 255)->unique();
            $table->enum('platform', PlatformPerangkat::values());

            $table->timestamp('terakhir_aktif_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'platform']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perangkat_token');
    }
};
