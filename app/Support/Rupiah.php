<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Pemformatan uang untuk lapis presentasi.
 *
 * Seluruh nilai uang disimpan sebagai integer rupiah (CLAUDE.md 11).
 * Titik pemisah ribuan ditambahkan di sini dan hanya di sini, begitu
 * sebuah angka diformat, ia berhenti menjadi angka dan tidak boleh
 * dihitung lagi.
 */
final class Rupiah
{
    /** Mis. 12500 → "Rp 12.500" */
    public static function format(?int $rupiah): string
    {
        return 'Rp '.number_format((int) $rupiah, 0, ',', '.');
    }

    /** Tanpa awalan, untuk kolom tabel yang sudah berjudul "Nilai (Rp)". */
    public static function angka(?int $rupiah): string
    {
        return number_format((int) $rupiah, 0, ',', '.');
    }

    /**
     * Bentuk ringkas untuk kartu statistik: 1.250.000 → "1,3 jt".
     *
     * Dipakai hanya ketika angka pastinya tidak menjadi dasar keputusan.
     * Untuk saldo, nilai setoran, dan tagihan, selalu pakai format()
     * penuh, pembulatan pada angka yang menyangkut uang seseorang
     * adalah cara tercepat kehilangan kepercayaan.
     */
    public static function ringkas(?int $rupiah): string
    {
        $nilai = (int) $rupiah;

        return match (true) {
            abs($nilai) >= 1_000_000_000 => 'Rp '.self::desimal($nilai / 1_000_000_000).' M',
            abs($nilai) >= 1_000_000 => 'Rp '.self::desimal($nilai / 1_000_000).' jt',
            abs($nilai) >= 1_000 => 'Rp '.self::desimal($nilai / 1_000).' rb',
            default => self::format($nilai),
        };
    }

    private static function desimal(float $nilai): string
    {
        return rtrim(rtrim(number_format($nilai, 1, ',', '.'), '0'), ',');
    }
}
