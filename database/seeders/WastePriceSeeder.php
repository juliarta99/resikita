<?php

namespace Database\Seeders;

use App\Models\WastePrice;
use Illuminate\Database\Seeder;

class WastePriceSeeder extends Seeder
{
    public function run(): void
    {
        $prices = [
            ['jenis_sampah' => 'Plastik PET (botol)', 'harga_per_kg' => 3000],
            ['jenis_sampah' => 'Plastik campuran',    'harga_per_kg' => 1500],
            ['jenis_sampah' => 'Kertas / HVS',        'harga_per_kg' => 2000],
            ['jenis_sampah' => 'Kardus',              'harga_per_kg' => 2500],
            ['jenis_sampah' => 'Logam / kaleng',      'harga_per_kg' => 5000],
            ['jenis_sampah' => 'Kaca / botol beling', 'harga_per_kg' => 1000],
            ['jenis_sampah' => 'Aluminium',           'harga_per_kg' => 12000],
        ];

        foreach ($prices as $p) {
            WastePrice::firstOrCreate(['jenis_sampah' => $p['jenis_sampah']], $p + ['satuan' => 'kg', 'is_active' => true]);
        }
    }
}
