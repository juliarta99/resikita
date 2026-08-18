<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

/** Pilihan nada bahasa untuk keluaran Asisten Konten UMKM. */
enum NadaKonten: string implements HasLabel
{
    use ProvidesOptions;

    case Informatif = 'informatif';
    case Hangat = 'hangat';
    case Persuasif = 'persuasif';

    public function label(): string
    {
        return match ($this) {
            self::Informatif => 'Informatif',
            self::Hangat => 'Hangat',
            self::Persuasif => 'Persuasif',
        };
    }

    /** Petunjuk gaya yang disisipkan ke instruksi sistem Gemini. */
    public function instruksi(): string
    {
        return match ($this) {
            self::Informatif => 'Lugas dan berbasis fakta. Tonjolkan bahan baku daur ulang, ukuran, dan kegunaan. Hindari kata sifat berlebihan.',
            self::Hangat => 'Akrab dan personal, seolah pemilik usaha bercerita sendiri. Boleh menyapa pembaca langsung.',
            self::Persuasif => 'Dorong pembaca bertindak. Tekankan manfaat dan dampak lingkungan, tutup dengan ajakan yang jelas.',
        };
    }
}
