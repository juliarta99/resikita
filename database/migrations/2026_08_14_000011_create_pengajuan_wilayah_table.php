<?php

declare(strict_types=1);

use App\Enums\StatusPengajuanWilayah;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Berkas pengajuan pendaftaran wilayah ke Resikita.
 *
 * Inilah pengganti kolom `users.nip` yang dihapus. Kewenangan
 * pemerintahan tidak lagi diakui dari sebuah kolom teks bebas yang bisa
 * diisi apa saja, melainkan dari berkas pengajuan berisi surat bukti
 * yang ditinjau super_admin. Persetujuan itulah yang mengaktifkan role
 * pemerintahan dan mengubah `wilayah.status_registrasi`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_wilayah', function (Blueprint $table) {
            $table->id();

            $table->foreignId('wilayah_id')
                ->constrained('wilayah')
                ->cascadeOnDelete();

            $table->string('pemohon_nama', 150);
            $table->string('pemohon_jabatan', 150);
            $table->string('pemohon_email', 191);
            $table->string('pemohon_phone', 20)->nullable();
            $table->string('instansi', 191);

            // Surat tugas atau surat keterangan kewenangan.
            $table->string('surat_path');

            $table->enum('status', StatusPengajuanWilayah::values())
                ->default(StatusPengajuanWilayah::Diajukan->value);

            // Alasan penolakan, ditampilkan kembali ke pemohon.
            $table->text('catatan')->nullable();

            $table->foreignId('ditinjau_oleh')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('ditinjau_at')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index(['wilayah_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_wilayah');
    }
};
