<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum StatusNotifikasi: string implements HasLabel
{
    use ProvidesOptions;

    case Terkirim = 'terkirim';
    case Gagal = 'gagal';
    case Dibaca = 'dibaca';

    public function label(): string
    {
        return match ($this) {
            self::Terkirim => 'Terkirim',
            self::Gagal => 'Gagal terkirim',
            self::Dibaca => 'Sudah dibaca',
        };
    }

    public function belumDibaca(): bool
    {
        return $this === self::Terkirim;
    }
}
