<?php

namespace App\Support;

use App\Models\User;

class RoleRedirect
{
    /**
     * Tentukan tujuan setelah login berdasarkan role user.
     */
    public static function for(User $user): string
    {
        return match (true) {
            $user->hasAnyRole(['super_admin', 'admin'])                          => '/admin',
            $user->hasRole('admin_dinas')                                        => '/dinas',
            $user->hasAnyRole(['bupati', 'camat', 'lurah', 'kepala_dinas_banjar']) => '/eksekutif',
            $user->hasRole('umkm')                                               => '/umkm',
            $user->hasRole('admin_tps')                                          => '/tps',
            $user->hasAnyRole(['admin_bank_sampah', 'petugas_bank_sampah'])      => '/bank-sampah',
            default                                                              => '/login',
        };
    }
}
