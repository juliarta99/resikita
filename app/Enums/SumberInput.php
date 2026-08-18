<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

/**
 * Cara pengguna memasukkan teks: diketik atau didiktekan.
 *
 * Dipakai `laporan.deskripsi_sumber` dan `chat_pesan.sumber_input`.
 * Kolom ini bukan hiasan, angkanya yang membuktikan klaim
 * inklusivitas di GEMASTIK bisa diuji, bukan sekadar dinyatakan.
 */
enum SumberInput: string implements HasLabel
{
    use ProvidesOptions;

    case Ketik = 'ketik';
    case Suara = 'suara';

    public function label(): string
    {
        return match ($this) {
            self::Ketik => 'Diketik',
            self::Suara => 'Masukan suara',
        };
    }

    public function ikon(): string
    {
        return $this === self::Suara ? 'microphone' : 'keyboard';
    }
}
