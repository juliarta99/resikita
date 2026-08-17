<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Produk;
use App\Models\User;

class ProdukPolicy
{
    /** Katalog produk terbuka untuk umum, termasuk tamu. */
    public function view(?User $user, Produk $produk): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::ProdukKelola->value) && $user->umkm_id !== null;
    }

    public function update(User $user, Produk $produk): bool
    {
        if ($user->roleUtama()?->isPlatform() === true) {
            return $user->can(Permission::ProdukKelola->value);
        }

        return $user->can(Permission::ProdukKelola->value)
            && $user->umkm_id === $produk->umkm_id;
    }

    public function delete(User $user, Produk $produk): bool
    {
        return $this->update($user, $produk);
    }
}
