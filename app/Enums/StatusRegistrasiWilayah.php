<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

/**
 * Tahap keikutsertaan sebuah wilayah di Resikita.
 *
 * Nilai ini yang menentukan waterfall penanggung jawab laporan
 * (CLAUDE.md 9.2), hanya wilayah `terverifikasi` yang boleh
 * dijadikan tujuan routing.
 */
enum StatusRegistrasiWilayah: string implements HasLabel
{
    use ProvidesOptions;

    case BelumTerjangkau = 'belum_terjangkau';
    case Diajukan = 'diajukan';
    case Terverifikasi = 'terverifikasi';
    case Ditolak = 'ditolak';

    public function label(): string
    {
        return match ($this) {
            self::BelumTerjangkau => 'Belum terjangkau',
            self::Diajukan => 'Menunggu verifikasi',
            self::Terverifikasi => 'Terverifikasi',
            self::Ditolak => 'Ditolak',
        };
    }

    /** Kelas warna badge, dipakai komponen <x-status-badge>. */
    public function warna(): string
    {
        return match ($this) {
            self::BelumTerjangkau => 'gray',
            self::Diajukan => 'amber',
            self::Terverifikasi => 'green',
            self::Ditolak => 'red',
        };
    }

    /** Hanya wilayah terverifikasi yang boleh menerima penugasan laporan. */
    public function bolehJadiPenanggungJawab(): bool
    {
        return $this === self::Terverifikasi;
    }

    /** Wilayah yang laporannya jatuh ke Fasilitator Wilayah. */
    public function perluPendampingan(): bool
    {
        return $this !== self::Terverifikasi;
    }
}
