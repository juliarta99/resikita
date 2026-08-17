<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\PenarikanSaldo;
use App\Models\User;

class PenarikanSaldoPolicy
{
    public function view(User $user, PenarikanSaldo $penarikan): bool
    {
        return $penarikan->user_id === $user->id
            || $user->can(Permission::PenarikanSetujui->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::PenarikanAjukan->value);
    }

    /**
     * Penyetuju tidak boleh menyetujui pengajuannya sendiri.
     *
     * Seorang admin juga punya dompet dan bisa mengajukan penarikan.
     * Tanpa pemeriksaan ini, ia bisa mencairkan saldonya sendiri tanpa
     * ada mata kedua.
     */
    public function tinjau(User $user, PenarikanSaldo $penarikan): bool
    {
        return $user->can(Permission::PenarikanSetujui->value)
            && $penarikan->user_id !== $user->id;
    }
}
