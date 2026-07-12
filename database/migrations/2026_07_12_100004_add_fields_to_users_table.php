<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();

            $table->string('nik', 16)->nullable()->unique()->after('name');
            $table->date('tanggal_lahir')->nullable()->after('nik');
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable()->after('tanggal_lahir');

            $table->string('phone')->nullable()->unique()->after('email');
            $table->timestamp('phone_verified_at')->nullable()->after('phone');
            $table->string('kode_qr')->nullable()->unique()->after('phone_verified_at');
            $table->boolean('is_active')->default(true)->after('kode_qr');

            $table->foreignId('kecamatan_id')->nullable()->after('is_active')
                  ->constrained('kecamatan')->nullOnDelete();
            $table->foreignId('kelurahan_id')->nullable()->after('kecamatan_id')
                  ->constrained('kelurahan')->nullOnDelete();
            $table->foreignId('banjar_id')->nullable()->after('kelurahan_id')
                  ->constrained('banjar_dinas')->nullOnDelete();

            $table->unsignedBigInteger('tps_id')->nullable()->after('banjar_id');
            $table->unsignedBigInteger('bank_sampah_id')->nullable()->after('tps_id');
            $table->unsignedBigInteger('umkm_id')->nullable()->after('bank_sampah_id');

            $table->decimal('lat', 10, 7)->nullable()->after('umkm_id');
            $table->decimal('lng', 10, 7)->nullable()->after('lat');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['kecamatan_id']);
            $table->dropForeign(['kelurahan_id']);
            $table->dropForeign(['banjar_id']);
            $table->dropColumn([
                'nik', 'tanggal_lahir', 'jenis_kelamin',
                'phone', 'phone_verified_at', 'kode_qr', 'is_active',
                'kecamatan_id', 'kelurahan_id', 'banjar_id',
                'tps_id', 'bank_sampah_id', 'umkm_id', 'lat', 'lng',
            ]);
        });
    }
};
