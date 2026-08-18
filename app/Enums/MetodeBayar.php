<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

/**
 * Cara membayar pesanan marketplace maupun iuran TPS.
 *
 * `saldo` memakai dompet Resikita, yang isinya berasal dari setoran
 * sampah. Ini simpul ekonomi sirkularnya: sampah yang disetor kembali
 * jadi daya beli di dalam ekosistem yang sama.
 */
enum MetodeBayar: string implements HasLabel
{
    use ProvidesOptions;

    case Saldo = 'saldo';
    case Midtrans = 'midtrans';

    public function label(): string
    {
        return match ($this) {
            self::Saldo => 'Saldo Resikita',
            self::Midtrans => 'Transfer / e-wallet',
        };
    }

    /** Pembayaran saldo tuntas seketika; Midtrans menunggu callback. */
    public function langsungLunas(): bool
    {
        return $this === self::Saldo;
    }

    public function butuhSnapToken(): bool
    {
        return $this === self::Midtrans;
    }
}
