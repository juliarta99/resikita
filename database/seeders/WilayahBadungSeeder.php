<?php

namespace Database\Seeders;

use App\Models\BanjarDinas;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use Illuminate\Database\Seeder;

class WilayahBadungSeeder extends Seeder
{
    public function run(): void
    {
        // 6 kecamatan di Kabupaten Badung
        $kecamatan = ['Petang', 'Abiansemal', 'Mengwi', 'Kuta Utara', 'Kuta', 'Kuta Selatan'];
        $refs = [];
        foreach ($kecamatan as $nama) {
            $refs[$nama] = Kecamatan::firstOrCreate(['nama' => $nama]);
        }

        // Contoh kelurahan/desa di Mengwi + banjar dinas di Sempidi
        $sempidi = Kelurahan::firstOrCreate(['kecamatan_id' => $refs['Mengwi']->id, 'nama' => 'Sempidi']);
        Kelurahan::firstOrCreate(['kecamatan_id' => $refs['Mengwi']->id, 'nama' => 'Lukluk']);
        Kelurahan::firstOrCreate(['kecamatan_id' => $refs['Mengwi']->id, 'nama' => 'Sading']);

        foreach (['Kangin', 'Kauh', 'Tengah', 'Delod Sema'] as $banjar) {
            BanjarDinas::firstOrCreate(['kelurahan_id' => $sempidi->id, 'nama' => 'Banjar ' . $banjar]);
        }
    }
}
