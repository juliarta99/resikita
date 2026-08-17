<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kunci asing `users` dipasang di akhir.
 *
 * Kolomnya sudah dibuat bersama tabel `users`, tapi tabel tujuannya,
 * `wilayah`, `bank_sampah`, `umkm`, baru ada setelah migration ini.
 * Semua memakai nullOnDelete: menghapus sebuah bank sampah tidak boleh
 * ikut menghapus akun orang yang pernah bekerja di sana.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('wilayah_id')->references('id')->on('wilayah')->nullOnDelete();
            $table->foreign('bank_sampah_id')->references('id')->on('bank_sampah')->nullOnDelete();
            $table->foreign('umkm_id')->references('id')->on('umkm')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['wilayah_id']);
            $table->dropForeign(['bank_sampah_id']);
            $table->dropForeign(['umkm_id']);
        });
    }
};
