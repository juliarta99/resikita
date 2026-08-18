<?php

declare(strict_types=1);

use App\Enums\NadaKonten;
use App\Enums\TujuanKonten;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Keluaran Asisten Konten UMKM.
 *
 * `is_ai_generated` default true dan tidak pernah dibiarkan kosong.
 * Kolom ini adalah dasar pelabelan ke pengguna: pembeli berhak tahu
 * bahwa teks atau sampul yang dilihatnya disusun dengan bantuan AI.
 *
 * `sampul_path` menyimpan hasil komposisi template di atas foto produk
 * asli. Foto produknya sendiri tetap di `produk_foto` dan tidak pernah
 * diganti citra hasil generate, lihat CLAUDE.md 10.3.
 *
 * `dipakai` menandai konten yang benar-benar diunggah UMKM, sehingga
 * fitur ini bisa dinilai dari pemakaian nyata, bukan dari jumlah
 * generate.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('konten_promosi', function (Blueprint $table) {
            $table->id();

            $table->foreignId('umkm_id')
                ->constrained('umkm')
                ->cascadeOnDelete();

            $table->foreignId('produk_id')
                ->nullable()
                ->constrained('produk')
                ->nullOnDelete();

            $table->enum('tujuan', TujuanKonten::values());
            $table->enum('nada', NadaKonten::values());

            $table->text('hasil_teks')->nullable();
            $table->json('hasil_hashtag')->nullable();
            $table->string('sampul_path')->nullable();

            $table->boolean('is_ai_generated')->default(true);
            $table->string('model_version', 50)->nullable();
            $table->json('raw_response')->nullable();

            $table->boolean('dipakai')->default(false);

            $table->timestamps();

            $table->index(['umkm_id', 'tujuan']);
            $table->index(['produk_id', 'tujuan']);
            $table->index('dipakai');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('konten_promosi');
    }
};
