<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\BankSampah;
use App\Models\User;
use App\Services\Wilayah\WilayahScopeService;

class BankSampahPolicy
{
    public function __construct(
        private readonly WilayahScopeService $scope,
    ) {}

    /** Direktori bank sampah terbuka untuk umum. */
    public function view(?User $user, BankSampah $bankSampah): bool
    {
        return true;
    }

    /**
     * Mengelola profil dan katalog harga hanya boleh oleh pengelola unit
     * itu sendiri, atau oleh pemerintah wilayah yang membawahinya.
     */
    public function update(User $user, BankSampah $bankSampah): bool
    {
        if ($user->bank_sampah_id === $bankSampah->id) {
            return $user->can(Permission::BankSampahKelola->value);
        }

        if ($user->roleUtama()?->isPlatform() === true) {
            return $user->can(Permission::BankSampahKelola->value);
        }

        return $user->can(Permission::BankSampahKelola->value)
            && $this->scope->berwenangAtas($user, $bankSampah->wilayah_id);
    }

    public function aturHarga(User $user, BankSampah $bankSampah): bool
    {
        return $user->can(Permission::BankSampahHarga->value)
            && $this->update($user, $bankSampah);
    }

    /** Melayani setoran hanya oleh pengelola unit itu sendiri. */
    public function layaniSetoran(User $user, BankSampah $bankSampah): bool
    {
        return $user->can(Permission::BankSampahSetor->value)
            && $user->bank_sampah_id === $bankSampah->id;
    }
}
