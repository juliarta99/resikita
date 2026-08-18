<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Permission as PermissionEnum;
use App\Enums\Role as RoleEnum;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Sepuluh role Resikita beserta matriks kewenangannya.
 *
 * Daftar role dan permission dibaca dari enum, bukan diketik ulang di
 * sini, supaya kode dan basis data tidak mungkin menyimpang.
 *
 * Role dan permission disemai hanya pada guard `web`. Yang membuatnya
 * ikut berlaku untuk permintaan bertoken adalah `$guard_name = 'web'`
 * pada model User, bukan kesamaan provider. Tanpa properti itu, Spatie
 * akan mencari permission bertanda `sanctum` saat melayani API dan tidak
 * menemukan apa pun.
 *
 * Satu set kewenangan, dipakai kedua kanal. Menyemai dua set akan
 * menciptakan dua sumber kebenaran yang bisa menyimpang diam-diam.
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $guard = 'web';

        foreach (PermissionEnum::cases() as $permission) {
            Permission::firstOrCreate([
                'name' => $permission->value,
                'guard_name' => $guard,
            ]);
        }

        foreach (RoleEnum::cases() as $roleEnum) {
            $role = Role::firstOrCreate([
                'name' => $roleEnum->value,
                'guard_name' => $guard,
            ]);

            // syncPermissions, bukan givePermissionTo: kalau sebuah
            // kewenangan dicabut dari matriks, menjalankan ulang seeder
            // harus benar-benar mencabutnya, bukan meninggalkannya
            // menempel dari penyemaian sebelumnya.
            $role->syncPermissions(PermissionEnum::nilaiUntukRole($roleEnum));
        }

        App::make(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
