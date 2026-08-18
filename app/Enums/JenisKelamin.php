<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum JenisKelamin: string implements HasLabel
{
    use ProvidesOptions;

    case LakiLaki = 'L';
    case Perempuan = 'P';

    public function label(): string
    {
        return match ($this) {
            self::LakiLaki => 'Laki-laki',
            self::Perempuan => 'Perempuan',
        };
    }
}
