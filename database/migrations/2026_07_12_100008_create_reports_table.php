<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pelapor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('kategori_id')->constrained('report_categories');
            $table->string('tiket_no')->unique();
            $table->string('judul');
            $table->text('deskripsi');
            $table->string('foto')->nullable();
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->string('alamat')->nullable();
            $table->foreignId('banjar_id')->nullable()->constrained('banjar_dinas')->nullOnDelete();
            $table->enum('status', ['menunggu', 'diverifikasi', 'ditugaskan', 'proses', 'selesai', 'ditolak'])->default('menunggu');
            $table->boolean('is_duplikat')->default(false);
            $table->foreignId('duplikat_of_id')->nullable()->constrained('reports')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
