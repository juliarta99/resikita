<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

/**
 * Pengirim satu pesan dalam sesi chat.
 *
 * Nilainya mengikuti istilah Gemini (`user` dan `model`) supaya riwayat
 * dari basis data bisa langsung dikirim ulang sebagai `contents`
 * tanpa lapisan penerjemah.
 */
enum PeranChat: string implements HasLabel
{
    use ProvidesOptions;

    case User = 'user';
    case Model = 'model';

    public function label(): string
    {
        return match ($this) {
            self::User => 'Anda',
            self::Model => 'Asisten Resikita',
        };
    }

    public function isPengguna(): bool
    {
        return $this === self::User;
    }
}
