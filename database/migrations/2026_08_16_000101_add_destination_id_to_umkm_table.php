<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Titik asal pengiriman milik tiap UMKM.
 *
 * Sebelumnya asal pengiriman dibaca dari satu nilai global di config,
 * yang keliru untuk marketplace banyak penjual: tiap pesanan berangkat
 * dari lokasi toko yang berbeda, dan menagih ongkir dari satu titik
 * tetap membuat selisihnya ditanggung entah oleh siapa.
 *
 * Aturan "satu pesanan hanya dari satu toko" (CLAUDE.md 11) yang membuat
 * ini bisa diselesaikan tanpa ambiguitas, satu pesanan selalu punya
 * tepat satu asal.
 *
 * Namanya menyamai `pesanan.destination_id`: keduanya id wilayah dari
 * penyedia ongkir yang sama, dan menyebut yang satu `origin_id` hanya
 * akan menyembunyikan bahwa isinya berasal dari daftar yang sama.
 *
 * Nullable, karena toko yang sudah terdaftar sebelum kolom ini ada tidak
 * boleh mendadak berhenti bisa menerima pesanan. Selama masih null,
 * ShippingService jatuh ke nilai config lama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('umkm', function (Blueprint $table): void {
            $table->unsignedInteger('destination_id')->nullable()->after('wilayah_id');
            $table->string('alamat_asal')->nullable()->after('destination_id');
        });
    }

    public function down(): void
    {
        Schema::table('umkm', function (Blueprint $table): void {
            $table->dropColumn(['destination_id', 'alamat_asal']);
        });
    }
};
