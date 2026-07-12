<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            WilayahBadungSeeder::class,
            WastePriceSeeder::class,
            ReportCategorySeeder::class,
            ProductCategorySeeder::class,
        ]);

        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@nitiresik.id'],
            ['name' => 'Super Admin', 'password' => Hash::make('password'), 'is_active' => true]
        );

        $superAdmin->assignRole('super_admin');
    }
}
