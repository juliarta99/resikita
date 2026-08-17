<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

/** Platform perangkat yang mendaftarkan token notifikasi dorong. */
enum PlatformPerangkat: string implements HasLabel
{
    use ProvidesOptions;

    case Android = 'android';
    case Ios = 'ios';
    case Web = 'web';

    public function label(): string
    {
        return match ($this) {
            self::Android => 'Android',
            self::Ios => 'iOS',
            self::Web => 'Web',
        };
    }

    public function isMobile(): bool
    {
        return $this !== self::Web;
    }
}
