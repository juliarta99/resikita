<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

/** Status satu transaksi setoran sampah di bank sampah. */
enum StatusSetoran: string implements HasLabel
{
    use ProvidesOptions;

    case Proses = 'proses';
    case Selesai = 'selesai';
    case Batal = 'batal';

    public function label(): string
    {
        return match ($this) {
            self::Proses => 'Sedang ditimbang',
            self::Selesai => 'Selesai',
            self::Batal => 'Dibatalkan',
        };
    }

    public function warna(): string
    {
        return match ($this) {
            self::Proses => 'amber',
            self::Selesai => 'green',
            self::Batal => 'red',
        };
    }

    /** Hanya setoran selesai yang menambah saldo dompet nasabah. */
    public function menambahSaldo(): bool
    {
        return $this === self::Selesai;
    }

    public function isFinal(): bool
    {
        return $this !== self::Proses;
    }
}
