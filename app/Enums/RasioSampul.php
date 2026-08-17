<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

/**
 * Bentuk kanvas sampul produk.
 *
 * Kanal sosial memotong gambar yang rasionya tidak sesuai, dan yang
 * terpotong hampir selalu bagian teks. Karena itu bentuknya dipilih
 * penjual sejak awal, bukan dipaksa persegi lalu dipangkas kemudian.
 *
 * Lebarnya tetap 1080 piksel pada ketiga bentuk: itu lebar unggahan yang
 * diterima semua kanal tanpa dikompres ulang lebih jauh.
 */
enum RasioSampul: string implements HasLabel
{
    use ProvidesOptions;

    case Persegi = 'persegi';
    case Potret = 'potret';
    case Cerita = 'cerita';

    public function label(): string
    {
        return match ($this) {
            self::Persegi => 'Persegi 1:1',
            self::Potret => 'Potret 4:5',
            self::Cerita => 'Cerita 9:16',
        };
    }

    public function deskripsi(): string
    {
        return match ($this) {
            self::Persegi => 'Aman di semua kanal.',
            self::Potret => 'Paling tinggi di feed.',
            self::Cerita => 'Story dan Reels.',
        };
    }

    public function lebar(): int
    {
        return 1080;
    }

    public function tinggi(): int
    {
        return match ($this) {
            self::Persegi => 1080,
            self::Potret => 1350,
            self::Cerita => 1920,
        };
    }

    /** Perbandingan sisi untuk pratinjau di antarmuka, mis. "4 / 5". */
    public function rasioCss(): string
    {
        return match ($this) {
            self::Persegi => '1 / 1',
            self::Potret => '4 / 5',
            self::Cerita => '9 / 16',
        };
    }
}
