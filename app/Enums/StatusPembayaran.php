<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

/**
 * Status satu baris pembayaran.
 *
 * Nilainya sengaja memakai istilah Midtrans (`pending`, `paid`,
 * `failed`, `expired`) karena kolom ini adalah cermin keadaan di
 * penyedia pembayaran, bukan keadaan bisnis Resikita. Keadaan bisnis
 * ada di `StatusPesanan`.
 */
enum StatusPembayaran: string implements HasLabel
{
    use ProvidesOptions;

    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu pembayaran',
            self::Paid => 'Lunas',
            self::Failed => 'Gagal',
            self::Expired => 'Kedaluwarsa',
        };
    }

    public function warna(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Paid => 'green',
            self::Failed, self::Expired => 'red',
        };
    }

    public function isLunas(): bool
    {
        return $this === self::Paid;
    }

    public function isFinal(): bool
    {
        return $this !== self::Pending;
    }

    /**
     * Terjemahkan `transaction_status` dari callback Midtrans.
     * Nilai tak dikenal dipetakan ke Pending, bukan diasumsikan lunas,
     * pembayaran hanya dianggap selesai kalau penyedia menyatakannya
     * secara eksplisit.
     */
    public static function dariMidtrans(string $status, ?string $fraudStatus = null): self
    {
        return match ($status) {
            'capture' => $fraudStatus === 'accept' ? self::Paid : self::Pending,
            'settlement' => self::Paid,
            'deny', 'cancel', 'failure' => self::Failed,
            'expire' => self::Expired,
            default => self::Pending,
        };
    }
}
