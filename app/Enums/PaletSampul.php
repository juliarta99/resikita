<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

/**
 * Palet warna sampul produk.
 *
 * Warna merek Resikita tetap menjadi bawaan, tapi tidak dipaksakan.
 * Sampul ini diunggah ke akun media sosial milik UMKM sendiri, dan toko
 * yang sudah punya warna sendiri tidak seharusnya dipaksa menyeragamkan
 * feed-nya dengan warna platform.
 *
 * Tiap palet menyebut lima peran sekaligus, bukan sekadar satu warna.
 * Itu yang membuat teks selalu punya pasangan latar yang kontras: gaya
 * mana pun yang dipilih, warna hurufnya diambil dari peran yang memang
 * dipasangkan dengan bidang tempat huruf itu berdiri.
 */
enum PaletSampul: string implements HasLabel
{
    use ProvidesOptions;

    case Hijau = 'hijau';
    case Malam = 'malam';
    case Kertas = 'kertas';
    case Terakota = 'terakota';
    case Laut = 'laut';
    case Senja = 'senja';

    public function label(): string
    {
        return match ($this) {
            self::Hijau => 'Hijau Resikita',
            self::Malam => 'Malam',
            self::Kertas => 'Kertas',
            self::Terakota => 'Terakota',
            self::Laut => 'Laut',
            self::Senja => 'Senja',
        };
    }

    /** Warna aksen: garis merek, bingkai, dan pil keterangan. */
    public function utama(): string
    {
        return match ($this) {
            self::Hijau => '#057D5D',
            self::Malam => '#F2C14E',
            self::Kertas => '#1F2937',
            self::Terakota => '#C2531A',
            self::Laut => '#0E6B8A',
            self::Senja => '#9B3B6A',
        };
    }

    /** Warna pekat untuk tirai di atas foto. */
    public function gelap(): string
    {
        return match ($this) {
            self::Hijau => '#023628',
            self::Malam => '#0B0B0F',
            self::Kertas => '#111827',
            self::Terakota => '#3D1608',
            self::Laut => '#052B3A',
            self::Senja => '#2E1023',
        };
    }

    /** Warna huruf di atas tirai atau bidang gelap. */
    public function teksGelap(): string
    {
        return '#FFFFFF';
    }

    /** Bidang padat untuk gaya berpanel. */
    public function panel(): string
    {
        return match ($this) {
            self::Hijau => '#057D5D',
            self::Malam => '#111116',
            self::Kertas => '#F7F5F0',
            self::Terakota => '#C2531A',
            self::Laut => '#0E6B8A',
            self::Senja => '#9B3B6A',
        };
    }

    /** Warna huruf di atas panel. */
    public function teksPanel(): string
    {
        return $this === self::Kertas ? '#1F2937' : '#FFFFFF';
    }

    /** Contoh warna untuk penanda pilihan di antarmuka. */
    public function contoh(): string
    {
        return $this->panel();
    }
}
