<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('tps_id')->references('id')->on('tps')->nullOnDelete();
            $table->foreign('bank_sampah_id')->references('id')->on('bank_sampah')->nullOnDelete();
            $table->foreign('umkm_id')->references('id')->on('umkm')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tps_id']);
            $table->dropForeign(['bank_sampah_id']);
            $table->dropForeign(['umkm_id']);
        });
    }
};
