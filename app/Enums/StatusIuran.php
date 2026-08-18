<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

/** Status tagihan iuran TPS untuk satu periode bulanan. */
enum StatusIuran: string implements HasLabel
{
    use ProvidesOptions;

    case Menunggu = 'menunggu';
    case Lunas = 'lunas';
    case Gagal = 'gagal';

    public function label(): string
    {
        return match ($this) {
            self::Menunggu => 'Belum dibayar',
            self::Lunas => 'Lunas',
            self::Gagal => 'Pembayaran gagal',
        };
    }

    public function warna(): string
    {
        return match ($this) {
            self::Menunggu => 'amber',
            self::Lunas => 'green',
            self::Gagal => 'red',
        };
    }

    /** Tagihan yang masih bisa dibayar ulang. */
    public function bisaDibayar(): bool
    {
        return $this !== self::Lunas;
    }
}
