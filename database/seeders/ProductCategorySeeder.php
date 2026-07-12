<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Kompos & Pupuk', 'Kerajinan Daur Ulang', 'Produk Ramah Lingkungan', 'Tas & Wadah Guna Ulang'] as $nama) {
            ProductCategory::firstOrCreate(['nama' => $nama]);
        }
    }
}
