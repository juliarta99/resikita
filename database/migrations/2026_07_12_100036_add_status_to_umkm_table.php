<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('umkm', function (Blueprint $table) {
            // aktif   = dibuat admin / sudah disetujui
            // menunggu = daftar mandiri via landing, menunggu verifikasi
            // ditolak  = pendaftaran ditolak admin
            $table->enum('status', ['menunggu', 'aktif', 'ditolak'])->default('aktif')->after('nama');
        });
    }

    public function down(): void
    {
        Schema::table('umkm', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
