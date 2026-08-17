<?php

declare(strict_types=1);

use App\Enums\StatusPenarikan;
use App\Enums\TipeTransaksiDompet;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dompet UMKM: saldo, mutasi, dan penarikan.
 *
 * Strukturnya sengaja mencerminkan dompet masyarakat, tapi tabelnya
 * dipisah karena pemiliknya berbeda jenis. Dompet masyarakat milik
 * `users`, dompet UMKM milik `umkm` sebagai badan usaha, satu UMKM bisa
 * berganti pengelola tanpa saldonya ikut berpindah tangan.
 *
 * Ketiganya dibuat dalam satu migration karena merupakan satu unit
 * perubahan skema yang tidak berguna kalau hanya sebagian diterapkan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('umkm_dompet', function (Blueprint $table) {
            $table->id();

            $table->foreignId('umkm_id')
                ->unique()
                ->constrained('umkm')
                ->cascadeOnDelete();

            $table->bigInteger('saldo')->default(0);

            $table->timestamps();
        });

        Schema::create('umkm_dompet_transaksi', function (Blueprint $table) {
            $table->id();

            $table->foreignId('umkm_dompet_id')
                ->constrained('umkm_dompet')
                ->cascadeOnDelete();

            $table->enum('tipe', TipeTransaksiDompet::values());

            $table->bigInteger('jumlah');
            $table->bigInteger('saldo_sebelum');
            $table->bigInteger('saldo_sesudah');

            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->string('keterangan', 191)->nullable();

            $table->timestamps();

            $table->index(['umkm_dompet_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });

        Schema::create('umkm_penarikan', function (Blueprint $table) {
            $table->id();

            $table->foreignId('umkm_id')
                ->constrained('umkm')
                ->cascadeOnDelete();

            $table->bigInteger('jumlah');

            $table->string('metode', 50)->default('transfer_bank');
            $table->string('nama_bank', 100)->nullable();
            $table->string('no_rekening', 50);
            $table->string('atas_nama', 150);

            $table->enum('status', StatusPenarikan::values())
                ->default(StatusPenarikan::Menunggu->value);

            $table->foreignId('disetujui_oleh')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('catatan')->nullable();

            $table->timestamps();

            $table->index(['umkm_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('umkm_penarikan');
        Schema::dropIfExists('umkm_dompet_transaksi');
        Schema::dropIfExists('umkm_dompet');
    }
};
