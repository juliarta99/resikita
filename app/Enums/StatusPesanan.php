<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

/** Daur hidup pesanan marketplace, dari checkout sampai diterima pembeli. */
enum StatusPesanan: string implements HasLabel
{
    use ProvidesOptions;

    case MenungguBayar = 'menunggu_bayar';
    case Dibayar = 'dibayar';
    case Dikemas = 'dikemas';
    case Dikirim = 'dikirim';
    case Selesai = 'selesai';
    case Dibatalkan = 'dibatalkan';

    public function label(): string
    {
        return match ($this) {
            self::MenungguBayar => 'Menunggu pembayaran',
            self::Dibayar => 'Sudah dibayar',
            self::Dikemas => 'Sedang dikemas',
            self::Dikirim => 'Dalam pengiriman',
            self::Selesai => 'Selesai',
            self::Dibatalkan => 'Dibatalkan',
        };
    }

    public function warna(): string
    {
        return match ($this) {
            self::MenungguBayar => 'amber',
            self::Dibayar => 'blue',
            self::Dikemas => 'cyan',
            self::Dikirim => 'orange',
            self::Selesai => 'green',
            self::Dibatalkan => 'red',
        };
    }

    /** Pembeli hanya boleh membatalkan selama belum dikemas. */
    public function bisaDibatalkanPembeli(): bool
    {
        return match ($this) {
            self::MenungguBayar, self::Dibayar => true,
            default => false,
        };
    }

    /** Ulasan baru terbuka setelah pesanan tuntas. */
    public function bisaDiulas(): bool
    {
        return $this === self::Selesai;
    }

    /** Stok produk sudah dipesan sejak checkout; pembatalan mengembalikannya. */
    public function perluKembalikanStok(): bool
    {
        return $this === self::Dibatalkan;
    }

    public function isFinal(): bool
    {
        return match ($this) {
            self::Selesai, self::Dibatalkan => true,
            default => false,
        };
    }

    /** @return array<int, self> */
    public function transisiSah(): array
    {
        return match ($this) {
            self::MenungguBayar => [self::Dibayar, self::Dibatalkan],
            self::Dibayar => [self::Dikemas, self::Dibatalkan],
            self::Dikemas => [self::Dikirim],
            self::Dikirim => [self::Selesai],
            self::Selesai, self::Dibatalkan => [],
        };
    }

    public function bolehPindahKe(self $tujuan): bool
    {
        return in_array($tujuan, $this->transisiSah(), strict: true);
    }
}
