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

        // Data demo lengkap seluruh tabel
        $this->call([
            DemoSeeder::class,
        ]);
    }
}