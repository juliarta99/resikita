<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role as RoleEnum;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Urutannya bukan selera.
     *
     * Role harus ada sebelum akun mana pun dibuat, wilayah sebelum akun
     * pemerintahan menunjuknya, dan master data sebelum laporan demo
     * memilih kategorinya. Membalik urutan menghasilkan galat kunci asing
     * yang penyebabnya jauh dari tempat kegagalannya terlihat.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            WilayahSeeder::class,
            MasterDataSeeder::class,
        ]);

        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@resikita.id'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        $superAdmin->assignRole(RoleEnum::SuperAdmin->value);

        /*
         * Data demo dipisahkan dan tidak pernah ikut di produksi. Kata
         * sandi seragam yang memudahkan demo adalah persis yang paling
         * berbahaya kalau sampai tersemai di basis data sungguhan.
         */
        if (! app()->isProduction()) {
            $this->call([
                DemoSeeder::class,
                DemoTransaksiSeeder::class,
                DemoInteraksiSeeder::class,
            ]);
        }
    }
}
