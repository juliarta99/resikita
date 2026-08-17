<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jejak peninjauan pendaftaran UMKM.
 *
 * Sebelumnya penolakan hanya mengubah `status` menjadi `ditolak` dan
 * tidak meninggalkan apa pun: tidak ada alasannya, tidak ada siapa yang
 * memutuskan, tidak ada kapan. Pemilik usaha karena itu tidak pernah
 * tahu apa yang harus diperbaiki, dan admin berikutnya tidak bisa
 * menelusuri keputusan pendahulunya.
 *
 * Bentuknya sengaja meniru `pengajuan_wilayah`, yang sudah menyimpan
 * `catatan`, `ditinjau_oleh`, dan `ditinjau_at` untuk alasan yang sama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('umkm', function (Blueprint $table): void {
            $table->text('catatan_verifikasi')->nullable()->after('is_verified');

            $table->foreignId('ditinjau_oleh')
                ->nullable()
                ->after('catatan_verifikasi')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('ditinjau_at')->nullable()->after('ditinjau_oleh');
        });
    }

    public function down(): void
    {
        Schema::table('umkm', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('ditinjau_oleh');
            $table->dropColumn(['catatan_verifikasi', 'ditinjau_at']);
        });
    }
};
