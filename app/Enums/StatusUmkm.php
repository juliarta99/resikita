<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

/** Status pendaftaran UMKM di marketplace Resikita. */
enum StatusUmkm: string implements HasLabel
{
    use ProvidesOptions;

    case Menunggu = 'menunggu';
    case Aktif = 'aktif';
    case Ditolak = 'ditolak';

    public function label(): string
    {
        return match ($this) {
            self::Menunggu => 'Menunggu verifikasi',
            self::Aktif => 'Aktif',
            self::Ditolak => 'Ditolak',
        };
    }

    public function warna(): string
    {
        return match ($this) {
            self::Menunggu => 'amber',
            self::Aktif => 'green',
            self::Ditolak => 'red',
        };
    }

    /** Hanya UMKM aktif yang produknya tampil di direktori dan marketplace. */
    public function bolehBerjualan(): bool
    {
        return $this === self::Aktif;
    }
}
