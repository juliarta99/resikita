<?php

declare(strict_types=1);

use App\Enums\PeranChat;
use App\Enums\SumberInput;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_pesan', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sesi_id')
                ->constrained('chat_sesi')
                ->cascadeOnDelete();

            $table->enum('role', PeranChat::values());
            $table->text('konten');

            // Diisi hanya untuk pesan dari pengguna.
            $table->enum('sumber_input', SumberInput::values())->nullable();

            // Menandai jawaban yang benar-benar dibacakan lewat TTS.
            $table->boolean('dibacakan')->default(false);

            $table->string('model_version', 50)->nullable();

            $table->timestamps();

            $table->index(['sesi_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_pesan');
    }
};
