<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Tambah kolom product_id (lewati bila sudah ada dari percobaan sebelumnya)
        if (! Schema::hasColumn('reviews', 'product_id')) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->foreignId('product_id')->nullable()->after('order_id')
                    ->constrained('products')->nullOnDelete();
            });
        }

        // 2) Tambah composite unique (order_id, product_id) DULU.
        //    Index ini diawali order_id sehingga bisa dipakai FK -> memungkinkan drop unique lama.
        if (! $this->hasIndex('reviews_order_id_product_id_unique')) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->unique(['order_id', 'product_id']);
            });
        }

        // 3) Baru drop unique lama pada order_id (kini FK bisa memakai composite di atas).
        if ($this->hasIndex('reviews_order_id_unique')) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->dropUnique('reviews_order_id_unique');
            });
        }
    }

    public function down(): void
    {
        if ($this->hasIndex('reviews_order_id_product_id_unique')) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->dropUnique(['order_id', 'product_id']);
            });
        }
        if (Schema::hasColumn('reviews', 'product_id')) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->dropConstrainedForeignId('product_id');
            });
        }
        if (! $this->hasIndex('reviews_order_id_unique')) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->unique('order_id');
            });
        }
    }

    private function hasIndex(string $name): bool
    {
        return collect(DB::select('SHOW INDEX FROM reviews'))
            ->pluck('Key_name')->contains($name);
    }
};