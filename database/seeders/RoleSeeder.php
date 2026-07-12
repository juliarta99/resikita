<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'super_admin', 'admin', 'admin_dinas',
            'bupati', 'camat', 'lurah', 'kepala_dinas_banjar',
            'umkm', 'admin_tps', 'admin_bank_sampah', 'petugas_bank_sampah',
            'masyarakat', 'petugas_lapangan',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }
}
