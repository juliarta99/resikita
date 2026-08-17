<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

/**
 * Tata letak sampul produk (CLAUDE.md 10.3).
 *
 * Tiap gaya hanya mengatur ke mana foto ditempatkan dan di mana teks
 * berdiri. Foto yang dipakai selalu foto asli unggahan penjual, tidak
 * satu pun gaya di sini menggantinya dengan citra buatan.
 *
 * Pilihan gayanya sengaja berbeda secara struktur, bukan sekadar beda
 * warna: penjual yang fotonya gelap butuh panel terpisah supaya teksnya
 * terbaca, sementara yang fotonya lapang lebih untung memakai tirai
 * transparan yang membiarkan produknya terlihat penuh.
 */
enum GayaSampul: string implements HasLabel
{
    use ProvidesOptions;

    case TiraiBawah = 'tirai_bawah';
    case KartuMengambang = 'kartu_mengambang';
    case PitaSamping = 'pita_samping';
    case BlokAtas = 'blok_atas';
    case BingkaiPenuh = 'bingkai_penuh';
    case SorotTengah = 'sorot_tengah';

    public function label(): string
    {
        return match ($this) {
            self::TiraiBawah => 'Tirai bawah',
            self::KartuMengambang => 'Kartu mengambang',
            self::PitaSamping => 'Pita samping',
            self::BlokAtas => 'Blok atas',
            self::BingkaiPenuh => 'Bingkai penuh',
            self::SorotTengah => 'Sorot tengah',
        };
    }

    public function deskripsi(): string
    {
        return match ($this) {
            self::TiraiBawah => 'Foto memenuhi bidang, teks di bawah gradasi gelap.',
            self::KartuMengambang => 'Teks dalam kartu membulat di atas foto.',
            self::PitaSamping => 'Panel warna di kiri, foto di kanan.',
            self::BlokAtas => 'Judul di blok warna atas, foto di bawahnya.',
            self::BingkaiPenuh => 'Foto berbingkai tebal, teks di pita bawah.',
            self::SorotTengah => 'Foto diredupkan, teks besar rata tengah.',
        };
    }

    /** Gaya yang memakai warna panel sebagai bidang teks, bukan tirai di atas foto. */
    public function memakaiPanel(): bool
    {
        return match ($this) {
            self::PitaSamping, self::BlokAtas, self::BingkaiPenuh => true,
            default => false,
        };
    }
}
