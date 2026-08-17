<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\KlasifikasiSampah;
use App\Models\User;

/**
 * Riwayat klasifikasi bersifat pribadi.
 *
 * Isinya foto-foto yang diambil pengguna di rumahnya sendiri, lengkap
 * dengan waktu pengambilan. Itu bukan data publik meski isinya sekadar
 * sampah.
 */
class KlasifikasiSampahPolicy
{
    public function view(User $user, KlasifikasiSampah $klasifikasi): bool
    {
        return $klasifikasi->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::KlasifikasiBuat->value);
    }

    public function delete(User $user, KlasifikasiSampah $klasifikasi): bool
    {
        return $klasifikasi->user_id === $user->id;
    }
}
