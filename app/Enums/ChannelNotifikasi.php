<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

/** Kanal pengiriman notifikasi. */
enum ChannelNotifikasi: string implements HasLabel
{
    use ProvidesOptions;

    case Inapp = 'inapp';
    case Wa = 'wa';

    public function label(): string
    {
        return match ($this) {
            self::Inapp => 'Dalam aplikasi',
            self::Wa => 'WhatsApp',
        };
    }

    /**
     * Kanal WhatsApp memanggil penyedia luar, jadi wajib lewat Job/Queue
     * (CLAUDE.md 3 butir 8). Notifikasi dalam aplikasi cukup ditulis
     * langsung ke basis data.
     */
    public function lewatQueue(): bool
    {
        return $this === self::Wa;
    }

    /** Kanal WhatsApp butuh `users.phone` terisi dan terverifikasi. */
    public function butuhNomorTelepon(): bool
    {
        return $this === self::Wa;
    }
}
