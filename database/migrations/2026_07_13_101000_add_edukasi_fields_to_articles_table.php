<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->unsignedBigInteger('dilihat')->default(0)->after('status');
            $table->boolean('is_unggulan')->default(false)->after('dilihat');
            $table->string('video_url')->nullable()->after('is_unggulan'); // opsional (mis. YouTube)
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn(['dilihat', 'is_unggulan', 'video_url']);
        });
    }
};
