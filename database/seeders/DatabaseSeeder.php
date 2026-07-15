<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Role wajib dibuat lebih dulu
        $this->call([
            RoleSeeder::class,
        ]);

        // Super admin utama
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@nitiresik.id'],
            ['name' => 'Super Admin', 'password' => Hash::make('password'), 'is_active' => true]
        );
        $superAdmin->assignRole('super_admin');

        // Data demo lengkap seluruh tabel.
        //
        // RealDataSeeder  : wilayah, pejabat, TPS3R, dan bank sampah Kab. Badung
        //                   berbasis data riil (lihat komentar di dalam berkas).
        // DemoSeeder      : data acak sepenuhnya — pakai bila butuh dataset
        //                   generik/besar untuk uji beban.
        //
        // Jangan menjalankan keduanya sekaligus: keduanya mengisi tabel yang
        // sama sehingga wilayah, warga, dan transaksi akan terduplikasi.
        $this->call([
            RealDataSeeder::class,
            // DemoSeeder::class,
        ]);
    }
}