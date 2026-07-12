<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waste_deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_sampah_id')->constrained('bank_sampah');
            $table->foreignId('petugas_id')->constrained('users');
            $table->foreignId('nasabah_id')->constrained('users');
            $table->decimal('total_berat', 10, 2)->default(0);
            $table->decimal('total_nilai', 14, 2)->default(0);
            $table->enum('status', ['proses', 'selesai', 'batal'])->default('proses');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waste_deposits');
    }
};
