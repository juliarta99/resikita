<?php

declare(strict_types=1);

use App\Enums\StatusArtikel;
use App\Enums\TipeArtikel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Materi literasi lingkungan.
 *
 * `teks_baca` adalah versi konten yang sudah dibersihkan dari markdown,
 * diisi otomatis oleh TeksBacaService saat artikel disimpan. Pembersihan
 * dilakukan di peladen, bukan di klien, supaya pemutar suara di web dan
 * di mobile membacakan teks yang persis sama. Kalau tiap klien
 * membersihkan sendiri, keduanya akan menyimpang perlahan.
 *
 * `didengarkan` dihitung terpisah dari `dilihat`. Angka itu yang
 * mengubah klaim inklusivitas dari pernyataan menjadi sesuatu yang
 * bisa ditunjukkan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artikel', function (Blueprint $table) {
            $table->id();

            $table->foreignId('penulis_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('kategori_id')
                ->nullable()
                ->constrained('artikel_kategori')
                ->nullOnDelete();

            $table->enum('tipe', TipeArtikel::values())
                ->default(TipeArtikel::Artikel->value);

            $table->string('judul', 191);
            $table->string('slug', 191)->unique();
            $table->longText('konten');

            // Versi bersih markdown untuk TTS.
            $table->longText('teks_baca')->nullable();
            $table->unsignedSmallInteger('estimasi_baca_menit')->nullable();

            $table->string('thumbnail')->nullable();
            $table->string('video_url')->nullable();

            $table->enum('status', StatusArtikel::values())
                ->default(StatusArtikel::Draft->value);

            $table->unsignedBigInteger('dilihat')->default(0);
            $table->unsignedBigInteger('didengarkan')->default(0);

            $table->boolean('is_unggulan')->default(false);
            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index(['kategori_id', 'status']);
            $table->index('is_unggulan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artikel');
    }
};
