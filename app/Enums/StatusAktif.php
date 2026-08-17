<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

/**
 * Status hidup/mati sederhana untuk entitas yang bisa dinonaktifkan
 * tanpa dihapus: bank sampah dan keanggotaan TPS.
 */
enum StatusAktif: string implements HasLabel
{
    use ProvidesOptions;

    case Aktif = 'aktif';
    case Nonaktif = 'nonaktif';

    public function label(): string
    {
        return match ($this) {
            self::Aktif => 'Aktif',
            self::Nonaktif => 'Nonaktif',
        };
    }

    public function warna(): string
    {
        return $this === self::Aktif ? 'green' : 'gray';
    }

    public function isAktif(): bool
    {
        return $this === self::Aktif;
    }
}
