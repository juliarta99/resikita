<?php

namespace Database\Seeders;

use App\Models\ReportCategory;
use Illuminate\Database\Seeder;

class ReportCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Sampah menumpuk',
            'Pembuangan sampah liar',
            'TPS penuh / meluber',
            'Saluran / got tersumbat sampah',
            'Sampah di sungai / pantai',
            'Pembakaran sampah',
        ];

        foreach ($categories as $nama) {
            ReportCategory::firstOrCreate(['nama' => $nama]);
        }
    }
}
