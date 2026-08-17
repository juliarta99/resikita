<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Artikel;
use App\Models\User;

class ArtikelPolicy
{
    /** Artikel terbit terbuka untuk umum; draf hanya untuk pengelola. */
    public function view(?User $user, Artikel $artikel): bool
    {
        if ($artikel->status->isTerbit()) {
            return true;
        }

        return $user?->can(Permission::ArtikelKelola->value) ?? false;
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::ArtikelKelola->value);
    }

    public function update(User $user, Artikel $artikel): bool
    {
        return $user->can(Permission::ArtikelKelola->value);
    }

    public function delete(User $user, Artikel $artikel): bool
    {
        return $user->can(Permission::ArtikelKelola->value);
    }
}
