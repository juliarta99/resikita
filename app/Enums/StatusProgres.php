<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

/** Jenis catatan progres yang dikirim petugas dari lapangan. */
enum StatusProgres: string implements HasLabel
{
    use ProvidesOptions;

    case Dikerjakan = 'dikerjakan';
    case Selesai = 'selesai';

    public function label(): string
    {
        return match ($this) {
            self::Dikerjakan => 'Pembaruan pengerjaan',
            self::Selesai => 'Laporan diselesaikan',
        };
    }

    /** Catatan penyelesaian wajib menyertakan foto bukti. */
    public function wajibFotoBukti(): bool
    {
        return $this === self::Selesai;
    }
}
