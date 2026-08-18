<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Jarak lingkaran besar antara dua titik di permukaan bumi.
 *
 * Dipakai dua tempat yang keduanya kritis: resolusi wilayah dari
 * koordinat laporan, dan pencarian laporan kembar dalam radius kecil.
 * Rumusnya dipusatkan di sini supaya keduanya memakai jari-jari bumi
 * dan pembulatan yang sama, dan supaya perhitungan bisa dilakukan di
 * basis data, memfilter ribuan baris di PHP bukan pilihan.
 */
final class Haversine
{
    /** Jari-jari rata-rata bumi dalam kilometer. */
    public const JARI_JARI_KM = 6371.0;

    /**
     * Tambahkan kolom terhitung `jarak_km` ke query, lalu saring dan
     * urutkan berdasarkan jarak dari titik acuan.
     *
     * `LEAST(1, ...)` menjaga argumen acos tetap di dalam domainnya;
     * tanpa itu galat pembulatan pada dua titik yang nyaris identik
     * bisa menghasilkan nilai sedikit di atas 1 dan MySQL mengembalikan
     * NULL, sehingga titik terdekat justru hilang dari hasil.
     *
     * @param  string  $kolomLat  Nama kolom lintang, boleh berkualifikasi tabel.
     * @param  string  $kolomLng  Nama kolom bujur.
     */
    public static function terapkan(
        Builder $query,
        float $latitude,
        float $longitude,
        ?float $radiusKm = null,
        string $kolomLat = 'latitude',
        string $kolomLng = 'longitude',
    ): Builder {
        $rumus = sprintf(
            '(%s * acos(LEAST(1, cos(radians(?)) * cos(radians(%s)) * cos(radians(%s) - radians(?)) + sin(radians(?)) * sin(radians(%s)))))',
            self::JARI_JARI_KM,
            $kolomLat,
            $kolomLng,
            $kolomLat,
        );

        $ikatan = [$latitude, $longitude, $latitude];

        $query
            ->whereNotNull($kolomLat)
            ->whereNotNull($kolomLng)
            ->selectRaw("*, $rumus as jarak_km", $ikatan)
            ->orderByRaw("$rumus asc", $ikatan);

        if ($radiusKm !== null) {
            $query->whereRaw("$rumus <= ?", [...$ikatan, $radiusKm]);
        }

        return $query;
    }

    /** Jarak dua titik dalam kilometer, dihitung di PHP. */
    public static function jarakKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return self::JARI_JARI_KM * 2 * asin(min(1.0, sqrt($a)));
    }

    /** Jarak dua titik dalam meter. */
    public static function jarakMeter(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        return self::jarakKm($lat1, $lng1, $lat2, $lng2) * 1000;
    }
}
