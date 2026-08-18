<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

/**
 * Jenis mutasi saldo dompet.
 *
 * `arah()` menentukan tanda perubahan saldo. Service dompet memakai
 * ini alih-alih menuliskan tanda plus/minus di tiap pemanggilan,
 * satu tempat yang menentukan apakah sebuah transaksi menambah atau
 * mengurangi, sehingga tidak mungkin tertukar.
 */
enum TipeTransaksiDompet: string implements HasLabel
{
    use ProvidesOptions;

    case Setor = 'setor';
    case Belanja = 'belanja';
    case Penarikan = 'penarikan';
    case Refund = 'refund';
    case Iuran = 'iuran';

    public function label(): string
    {
        return match ($this) {
            self::Setor => 'Setoran sampah',
            self::Belanja => 'Belanja produk',
            self::Penarikan => 'Penarikan saldo',
            self::Refund => 'Pengembalian dana',
            self::Iuran => 'Iuran TPS',
        };
    }

    /** 1 untuk pemasukan, -1 untuk pengeluaran. */
    public function arah(): int
    {
        return match ($this) {
            self::Setor, self::Refund => 1,
            self::Belanja, self::Penarikan, self::Iuran => -1,
        };
    }

    public function isPemasukan(): bool
    {
        return $this->arah() === 1;
    }

    /**
     * Terapkan mutasi ke saldo. Nilai `$jumlah` selalu positif;
     * arah ditentukan oleh tipe, bukan oleh pemanggil.
     */
    public function terapkan(int $saldo, int $jumlah): int
    {
        return $saldo + ($this->arah() * abs($jumlah));
    }
}
