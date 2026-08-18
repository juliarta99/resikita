<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

/** Bentuk materi literasi di pustaka edukasi Resikita. */
enum TipeArtikel: string implements HasLabel
{
    use ProvidesOptions;

    case Artikel = 'artikel';
    case Panduan = 'panduan';
    case Tutorial = 'tutorial';
    case Jurnal = 'jurnal';

    public function label(): string
    {
        return match ($this) {
            self::Artikel => 'Artikel',
            self::Panduan => 'Panduan',
            self::Tutorial => 'Tutorial',
            self::Jurnal => 'Jurnal',
        };
    }

    public function warna(): string
    {
        return match ($this) {
            self::Artikel => 'blue',
            self::Panduan => 'green',
            self::Tutorial => 'purple',
            self::Jurnal => 'gray',
        };
    }
}
