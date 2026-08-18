<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

/** Status berkas pengajuan pendaftaran wilayah yang ditinjau super_admin. */
enum StatusPengajuanWilayah: string implements HasLabel
{
    use ProvidesOptions;

    case Diajukan = 'diajukan';
    case Disetujui = 'disetujui';
    case Ditolak = 'ditolak';

    public function label(): string
    {
        return match ($this) {
            self::Diajukan => 'Menunggu ditinjau',
            self::Disetujui => 'Disetujui',
            self::Ditolak => 'Ditolak',
        };
    }

    public function warna(): string
    {
        return match ($this) {
            self::Diajukan => 'amber',
            self::Disetujui => 'green',
            self::Ditolak => 'red',
        };
    }

    /** Pengajuan yang sudah ditinjau tidak boleh diubah lagi. */
    public function sudahFinal(): bool
    {
        return $this !== self::Diajukan;
    }
}
