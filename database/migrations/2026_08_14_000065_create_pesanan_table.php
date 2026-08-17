<?php

declare(strict_types=1);

use App\Enums\MetodeBayar;
use App\Enums\StatusPesanan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pesanan marketplace.
 *
 * `umkm_id` ada di tingkat pesanan, bukan hanya di item. Itu penegakan
 * aturan "satu pesanan hanya dari satu toko" di tingkat skema:
 * keranjang berisi produk dari beberapa UMKM dipecah menjadi beberapa
 * pesanan saat checkout. Alasannya praktis, ongkos kirim, resi, dan
 * saldo penjual semuanya per toko.
 *
 * `nama_penerima` dan `phone_penerima` terpisah dari data pengguna
 * karena penerima paket tidak selalu pembelinya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pesanan', function (Blueprint $table) {
            $table->id();

            $table->string('kode', 30)->unique();

            $table->foreignId('user_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('umkm_id')
                ->constrained('umkm')
                ->restrictOnDelete();

            // Semua nilai rupiah penuh sebagai integer.
            $table->bigInteger('subtotal');
            $table->bigInteger('ongkir')->default(0);
            $table->bigInteger('total');

            $table->enum('metode_bayar', MetodeBayar::values());
            $table->enum('status', StatusPesanan::values())
                ->default(StatusPesanan::MenungguBayar->value);

            $table->string('nama_penerima', 150);
            $table->string('phone_penerima', 20);
            $table->text('alamat_kirim');

            // id district tujuan dari RajaOngkir V2.
            $table->unsignedInteger('destination_id')->nullable();
            $table->string('kurir', 50)->nullable();
            $table->string('layanan_kurir', 100)->nullable();
            $table->string('no_resi', 100)->nullable();

            $table->text('snap_token')->nullable();

            $table->timestamp('dibayar_at')->nullable();
            $table->timestamp('dikirim_at')->nullable();
            $table->timestamp('selesai_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['umkm_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesanan');
    }
};
