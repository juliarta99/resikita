<?php

declare(strict_types=1);

use App\Enums\ChannelNotifikasi;
use App\Enums\StatusNotifikasi;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifikasi', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('tipe', 100);
            $table->enum('channel', ChannelNotifikasi::values())
                ->default(ChannelNotifikasi::Inapp->value);

            $table->string('judul', 191);
            $table->text('pesan');

            // Tujuan saat notifikasi diketuk, dipakai web maupun mobile.
            $table->string('action_url')->nullable();

            $table->enum('status', StatusNotifikasi::values())
                ->default(StatusNotifikasi::Terkirim->value);

            // Id pesan dari penyedia, untuk menelusuri pengiriman WhatsApp.
            $table->string('provider_ref', 191)->nullable();

            $table->timestamp('dibaca_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasi');
    }
};
