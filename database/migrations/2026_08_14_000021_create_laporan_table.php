<?php

declare(strict_types=1);

use App\Enums\AlasanRouting;
use App\Enums\PenanggungJawabType;
use App\Enums\StatusLaporan;
use App\Enums\SumberInput;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Laporan sampah dari masyarakat, tabel paling penting di Resikita.
 *
 * Dua keputusan desain di sini perlu bisa dipertahankan saat sidang:
 *
 * 1. Empat kolom wilayah didenormalisasi. Sebuah titik koordinat
 *    diselesaikan sekali oleh WilayahResolverService menjadi desa,
 *    kecamatan, kabupaten, dan provinsi, lalu keempatnya disimpan di
 *    baris ini. Akibatnya analitik tingkat provinsi cukup satu klausa
 *    WHERE, bukan penelusuran rekursif naik hierarki tiap kali.
 *
 * 2. Keputusan routing disimpan, bukan dihitung ulang. `alasan_routing`
 *    merekam mengapa laporan jatuh ke pihak tertentu. Tanpa itu,
 *    pertanyaan "kenapa laporan ini ditangani fasilitator" hanya bisa
 *    dijawab dengan menebak ulang waterfall memakai status wilayah
 *    hari ini, padahal status itu mungkin sudah berubah sejak laporan
 *    masuk.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laporan', function (Blueprint $table) {
            $table->id();

            // Format RSK-YYYYMM-XXXXX, dipakai warga untuk melacak laporan.
            $table->string('tiket', 20)->unique();

            $table->foreignId('pelapor_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('kategori_id')
                ->constrained('laporan_kategori')
                ->restrictOnDelete();

            $table->string('judul', 191);
            $table->text('deskripsi');

            // Menandai laporan yang dibuat lewat masukan suara. Kolom ini
            // yang membuat klaim inklusivitas bisa diukur, bukan sekadar
            // dinyatakan di proposal.
            $table->enum('deskripsi_sumber', SumberInput::values())
                ->default(SumberInput::Ketik->value);

            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('alamat')->nullable();

            // Hasil resolusi wilayah, didenormalisasi dengan sengaja.
            $table->foreignId('desa_id')->nullable()->constrained('wilayah')->nullOnDelete();
            $table->foreignId('kecamatan_id')->nullable()->constrained('wilayah')->nullOnDelete();
            $table->foreignId('kabupaten_id')->nullable()->constrained('wilayah')->nullOnDelete();
            $table->foreignId('provinsi_id')->nullable()->constrained('wilayah')->nullOnDelete();

            // Hasil waterfall penanggung jawab (CLAUDE.md 9.2).
            $table->enum('penanggung_jawab_type', PenanggungJawabType::values())->nullable();
            $table->foreignId('penanggung_jawab_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('alasan_routing', AlasanRouting::values())->nullable();

            $table->enum('status', StatusLaporan::values())
                ->default(StatusLaporan::Baru->value);

            // Duplikat ditawarkan untuk digabung, tidak pernah ditolak.
            $table->boolean('is_duplikat')->default(false);
            $table->foreignId('duplikat_of_id')->nullable()->constrained('laporan')->nullOnDelete();

            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diverifikasi_at')->nullable();

            // Dasar perhitungan waktu respons di analitik wilayah.
            $table->timestamp('selesai_at')->nullable();

            $table->timestamps();

            // Index gabungan wajib, setiap dasbor pemerintahan menyaring
            // berdasarkan satu kolom wilayah plus status.
            $table->index(['kabupaten_id', 'status']);
            $table->index(['provinsi_id', 'status']);
            $table->index(['desa_id', 'status']);
            $table->index(['latitude', 'longitude']);
            $table->index(['penanggung_jawab_type', 'penanggung_jawab_id']);
            $table->index('alasan_routing');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan');
    }
};
