<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

/** Status satu baris penugasan petugas atas sebuah laporan. */
enum StatusPenugasan: string implements HasLabel
{
    use ProvidesOptions;

    case Ditugaskan = 'ditugaskan';
    case Dikerjakan = 'dikerjakan';
    case Selesai = 'selesai';
    case Dibatalkan = 'dibatalkan';

    public function label(): string
    {
        return match ($this) {
            self::Ditugaskan => 'Ditugaskan',
            self::Dikerjakan => 'Sedang dikerjakan',
            self::Selesai => 'Selesai',
            self::Dibatalkan => 'Dibatalkan',
        };
    }

    public function warna(): string
    {
        return match ($this) {
            self::Ditugaskan => 'amber',
            self::Dikerjakan => 'orange',
            self::Selesai => 'green',
            self::Dibatalkan => 'gray',
        };
    }

    /** Penugasan yang masih membebani petugas dan tampil di daftar tugasnya. */
    public function isAktif(): bool
    {
        return match ($this) {
            self::Ditugaskan, self::Dikerjakan => true,
            default => false,
        };
    }

    /** Status laporan yang selaras dengan status penugasan ini. */
    public function statusLaporan(): ?StatusLaporan
    {
        return match ($this) {
            self::Ditugaskan => StatusLaporan::Ditugaskan,
            self::Dikerjakan => StatusLaporan::Dikerjakan,
            self::Selesai => StatusLaporan::Selesai,
            self::Dibatalkan => null,
        };
    }
}
