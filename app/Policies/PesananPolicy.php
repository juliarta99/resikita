<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Pesanan;
use App\Models\User;

/**
 * Sebuah pesanan punya dua pihak berkepentingan dengan hak berbeda:
 * pembeli boleh membatalkan dan mengulas, penjual boleh mengemas dan
 * mengirim. Tidak ada yang boleh melakukan keduanya.
 */
class PesananPolicy
{
    public function view(User $user, Pesanan $pesanan): bool
    {
        return $this->pembeli($user, $pesanan)
            || $this->penjual($user, $pesanan)
            || ($user->roleUtama()?->isPlatform() ?? false);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::PesananBuat->value);
    }

    public function batalkan(User $user, Pesanan $pesanan): bool
    {
        return $this->pembeli($user, $pesanan)
            && $pesanan->status->bisaDibatalkanPembeli();
    }

    public function ulas(User $user, Pesanan $pesanan): bool
    {
        return $this->pembeli($user, $pesanan)
            && $pesanan->status->bisaDiulas();
    }

    public function bayarUlang(User $user, Pesanan $pesanan): bool
    {
        return $this->pembeli($user, $pesanan);
    }

    /** Kelola sisi penjual: mengemas, mengirim, mencatat resi. */
    public function kelola(User $user, Pesanan $pesanan): bool
    {
        return $this->penjual($user, $pesanan)
            && $user->can(Permission::PesananKelola->value);
    }

    private function pembeli(User $user, Pesanan $pesanan): bool
    {
        return $pesanan->user_id === $user->id;
    }

    private function penjual(User $user, Pesanan $pesanan): bool
    {
        return $user->umkm_id !== null && $user->umkm_id === $pesanan->umkm_id;
    }
}
