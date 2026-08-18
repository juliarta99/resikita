<?php

declare(strict_types=1);

use App\Enums\KategoriSampah;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hasil klasifikasi sampah oleh Gemini.
 *
 * Gabungan dua tabel skema lama, `classifications` dan
 * `waste_classifications`, yang melayani fungsi sama dengan kolom
 * saling tumpang tindih dan tidak pernah sinkron.
 *
 * Perubahan penting: `kategori` dulu varchar bebas, sekarang enum
 * tertutup lima nilai. Selama kolomnya bebas, model AI boleh mengarang
 * kategori apa pun dan tidak ada yang bisa dihitung. Tiga di antaranya,
 * b3, residu, elektronik, adalah bukti di tingkat skema bahwa Resikita
 * memilah lebih dalam daripada organik/anorganik.
 *
 * `model_version` menyimpan model yang dipakai, supaya hasil lama bisa
 * ditelusuri ketika model diganti.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('klasifikasi_sampah', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('foto_path');

            $table->string('jenis', 150);
            $table->enum('kategori', KategoriSampah::values());
            $table->string('material', 100)->nullable();

            // 0–100. Ditampilkan ke pengguna supaya hasil dengan keyakinan
            // rendah tidak diperlakukan sebagai kepastian.
            $table->decimal('confidence', 5, 2);

            $table->boolean('dapat_didaur_ulang')->default(false);
            $table->decimal('estimasi_berat_kg', 8, 3)->nullable();

            // Rupiah penuh sebagai integer, bukan decimal.
            $table->bigInteger('estimasi_nilai')->nullable();

            $table->json('langkah_pengolahan');
            $table->text('rekomendasi_daur_ulang')->nullable();
            $table->text('catatan')->nullable();

            $table->string('model_version', 50)->nullable();
            $table->json('raw_response')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('kategori');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('klasifikasi_sampah');
    }
};
