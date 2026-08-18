<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

/**
 * Pembedaan TPS biasa dan TPS3R.
 *
 * Penting untuk literasi: TPS hanya memindahkan sampah, TPS3R mengolah
 * sebagiannya di tempat. Membedakan keduanya di direktori publik
 * membantu warga tahu ke mana sampah terpilahnya sebaiknya dibawa.
 */
enum JenisTps: string implements HasLabel
{
    use ProvidesOptions;

    case Tps = 'tps';
    case Tps3r = 'tps3r';

    public function label(): string
    {
        return match ($this) {
            self::Tps => 'TPS',
            self::Tps3r => 'TPS3R',
        };
    }

    public function deskripsi(): string
    {
        return match ($this) {
            self::Tps => 'Tempat penampungan sementara. Sampah dikumpulkan lalu diangkut ke TPA.',
            self::Tps3r => 'Tempat pengolahan sampah dengan prinsip reduce, reuse, recycle. Sebagian sampah diolah di lokasi.',
        };
    }

    public function warna(): string
    {
        return $this === self::Tps3r ? 'green' : 'blue';
    }

    /** TPS3R mengolah sampah di tempat, bukan sekadar menampung. */
    public function mengolahDiTempat(): bool
    {
        return $this === self::Tps3r;
    }
}
