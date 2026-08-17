<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

/** Keluaran yang diminta UMKM dari Asisten Konten (CLAUDE.md 10.3). */
enum TujuanKonten: string implements HasLabel
{
    use ProvidesOptions;

    case Instagram = 'instagram';
    case SampulProduk = 'sampul_produk';
    case DeskripsiProduk = 'deskripsi_produk';

    public function label(): string
    {
        return match ($this) {
            self::Instagram => 'Caption Instagram',
            self::SampulProduk => 'Sampul produk',
            self::DeskripsiProduk => 'Deskripsi produk',
        };
    }

    public function deskripsi(): string
    {
        return match ($this) {
            self::Instagram => 'Caption siap unggah beserta tagar yang relevan.',
            self::SampulProduk => 'Gambar sampul hasil komposisi template di atas foto produk asli.',
            self::DeskripsiProduk => 'Deskripsi untuk halaman produk di marketplace.',
        };
    }

    /** Tujuan yang menghasilkan berkas gambar, bukan teks. */
    public function menghasilkanGambar(): bool
    {
        return $this === self::SampulProduk;
    }

    /** Hanya caption Instagram yang membawa daftar tagar. */
    public function menghasilkanHashtag(): bool
    {
        return $this === self::Instagram;
    }

    /**
     * Sampul disusun dari template di atas foto asli UMKM.
     * Foto produk tidak pernah diganti citra hasil generate, ini
     * perlindungan konsumen, bukan pilihan estetika (CLAUDE.md 10.3).
     */
    public function butuhFotoAsli(): bool
    {
        return $this === self::SampulProduk;
    }
}
