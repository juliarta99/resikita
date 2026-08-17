<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

/**
 * Empat tingkat hierarki wilayah administrasi Indonesia.
 *
 * Urutan case sengaja dari yang terluas ke yang tersempit, beberapa
 * pembantu di bawah bergantung pada urutan itu.
 */
enum TingkatWilayah: string implements HasLabel
{
    use ProvidesOptions;

    case Provinsi = 'provinsi';
    case Kabupaten = 'kabupaten';
    case Kecamatan = 'kecamatan';
    case Desa = 'desa';

    public function label(): string
    {
        return match ($this) {
            self::Provinsi => 'Provinsi',
            self::Kabupaten => 'Kabupaten/Kota',
            self::Kecamatan => 'Kecamatan',
            self::Desa => 'Desa/Kelurahan',
        };
    }

    /** Kedalaman hierarki, 1 untuk provinsi sampai 4 untuk desa. */
    public function depth(): int
    {
        return match ($this) {
            self::Provinsi => 1,
            self::Kabupaten => 2,
            self::Kecamatan => 3,
            self::Desa => 4,
        };
    }

    /** Tingkat induk langsung; null untuk provinsi yang tidak punya induk. */
    public function parent(): ?self
    {
        return match ($this) {
            self::Provinsi => null,
            self::Kabupaten => self::Provinsi,
            self::Kecamatan => self::Kabupaten,
            self::Desa => self::Kecamatan,
        };
    }

    /** Tingkat anak langsung; null untuk desa yang merupakan daun hierarki. */
    public function child(): ?self
    {
        return match ($this) {
            self::Provinsi => self::Kabupaten,
            self::Kabupaten => self::Kecamatan,
            self::Kecamatan => self::Desa,
            self::Desa => null,
        };
    }

    /**
     * Panjang kode wilayah Kemendagri untuk tingkat ini, dalam format
     * bertitik sebagaimana disimpan di kolom `wilayah.kode`.
     *
     * Contoh: 51 · 51.03 · 51.03.05 · 51.03.05.2001
     *
     * Format bertitik dipilih bukan karena keterbacaan semata: karena
     * kode anak selalu diawali kode induknya, seluruh keturunan sebuah
     * wilayah bisa dicari dengan satu `kode LIKE '51.03.%'` yang memakai
     * index, tanpa query rekursif. WilayahScopeService bergantung pada
     * sifat ini.
     */
    public function panjangKode(): int
    {
        return match ($this) {
            self::Provinsi => 2,
            self::Kabupaten => 5,
            self::Kecamatan => 8,
            self::Desa => 13,
        };
    }

    /** Pola kode yang sah untuk tingkat ini. */
    public function polaKode(): string
    {
        return match ($this) {
            self::Provinsi => '/^\d{2}$/',
            self::Kabupaten => '/^\d{2}\.\d{2}$/',
            self::Kecamatan => '/^\d{2}\.\d{2}\.\d{2}$/',
            self::Desa => '/^\d{2}\.\d{2}\.\d{2}\.\d{4}$/',
        };
    }

    public function kodeValid(string $kode): bool
    {
        return preg_match($this->polaKode(), $kode) === 1;
    }

    /** Tebak tingkat dari bentuk kodenya. */
    public static function dariKode(string $kode): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->kodeValid($kode)) {
                return $case;
            }
        }

        return null;
    }

    /** Kode induk, mis. 51.03.05 menjadi 51.03. Null untuk provinsi. */
    public static function kodeInduk(string $kode): ?string
    {
        $posisi = strrpos($kode, '.');

        return $posisi === false ? null : substr($kode, 0, $posisi);
    }

    /**
     * Tiga tingkat yang bisa memegang kewenangan pemerintahan di Resikita.
     * Kecamatan sengaja tidak termasuk, lihat CLAUDE.md 6.1: kecamatan
     * tetap ada di hierarki wilayah tapi bukan tingkat kewenangan.
     */
    public function isTingkatKewenangan(): bool
    {
        return $this !== self::Kecamatan;
    }
}
