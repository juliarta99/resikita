<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

/**
 * Jenis pihak yang memegang sebuah laporan.
 *
 * Nilainya sengaja sama persis dengan nama role terkait, tapi enum ini
 * dipisahkan dari `Role` karena maknanya berbeda: `Role` menyatakan
 * siapa seorang pengguna, `PenanggungJawabType` menyatakan dalam
 * kapasitas apa laporan itu dipegang saat routing dijalankan.
 */
enum PenanggungJawabType: string implements HasLabel
{
    use ProvidesOptions;

    case AdminKabupaten = 'admin_kabupaten';
    case AdminProvinsi = 'admin_provinsi';
    case KepalaDesa = 'kepala_desa';
    case FasilitatorWilayah = 'fasilitator_wilayah';

    public function label(): string
    {
        return $this->role()->label();
    }

    public function role(): Role
    {
        return match ($this) {
            self::AdminKabupaten => Role::AdminKabupaten,
            self::AdminProvinsi => Role::AdminProvinsi,
            self::KepalaDesa => Role::KepalaDesa,
            self::FasilitatorWilayah => Role::FasilitatorWilayah,
        };
    }

    /** Penanganan oleh fasilitator berarti wilayahnya belum ikut Resikita. */
    public function isPendampingan(): bool
    {
        return $this === self::FasilitatorWilayah;
    }
}
