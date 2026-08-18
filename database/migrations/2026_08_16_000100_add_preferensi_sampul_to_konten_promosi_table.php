<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pilihan tampilan sampul milik penjual.
 *
 * Disimpan sebagai satu kolom json, bukan dipecah menjadi kolom gaya,
 * palet, rasio, dan sederet boolean. Daftar pilihannya akan bertambah
 * setiap kali gaya baru ditambahkan, dan penambahan itu tidak sepadan
 * dengan migration pada tabel yang sudah menyimpan draf pengguna.
 *
 * Nilainya dibaca lewat App\Support\PreferensiSampul, yang selalu jatuh
 * ke bawaan untuk pilihan yang tidak dikenali. Baris lama yang bernilai
 * null karena itu tetap terbaca tanpa perlu diisi mundur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('konten_promosi', function (Blueprint $table): void {
            $table->json('preferensi_sampul')->nullable()->after('sampul_path');
        });
    }

    public function down(): void
    {
        Schema::table('konten_promosi', function (Blueprint $table): void {
            $table->dropColumn('preferensi_sampul');
        });
    }
};
