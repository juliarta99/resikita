<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum StatusArtikel: string implements HasLabel
{
    use ProvidesOptions;

    case Draft = 'draft';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Published => 'Terbit',
        };
    }

    public function warna(): string
    {
        return $this === self::Published ? 'green' : 'gray';
    }

    public function isTerbit(): bool
    {
        return $this === self::Published;
    }
}
