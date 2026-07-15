<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('umkm_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('umkm_id')->constrained('umkm')->cascadeOnDelete();
            $table->decimal('jumlah', 15, 2);
            $table->string('nama_bank');
            $table->string('no_rekening');
            $table->string('atas_nama');
            $table->string('status')->default('menunggu'); // menunggu / disetujui / ditolak
            $table->string('catatan')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('umkm_withdrawals');
    }
};
