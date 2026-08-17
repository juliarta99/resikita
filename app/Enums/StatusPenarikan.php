<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

/** Status pengajuan penarikan saldo, baik oleh masyarakat maupun UMKM. */
enum StatusPenarikan: string implements HasLabel
{
    use ProvidesOptions;

    case Menunggu = 'menunggu';
    case Disetujui = 'disetujui';
    case Ditolak = 'ditolak';
    case Selesai = 'selesai';

    public function label(): string
    {
        return match ($this) {
            self::Menunggu => 'Menunggu persetujuan',
            self::Disetujui => 'Disetujui, sedang ditransfer',
            self::Ditolak => 'Ditolak',
            self::Selesai => 'Dana sudah ditransfer',
        };
    }

    public function warna(): string
    {
        return match ($this) {
            self::Menunggu => 'amber',
            self::Disetujui => 'blue',
            self::Ditolak => 'red',
            self::Selesai => 'green',
        };
    }

    /**
     * Saldo sudah dipotong sejak pengajuan dibuat, agar dana tidak bisa
     * dipakai dua kali sambil menunggu persetujuan. Penolakan berarti
     * saldo harus dikembalikan.
     */
    public function perluKembalikanSaldo(): bool
    {
        return $this === self::Ditolak;
    }

    public function isFinal(): bool
    {
        return match ($this) {
            self::Ditolak, self::Selesai => true,
            default => false,
        };
    }
}
