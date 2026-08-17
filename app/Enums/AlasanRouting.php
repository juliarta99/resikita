<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

/**
 * Jejak audit keputusan routing laporan (CLAUDE.md 9.2).
 *
 * Disimpan supaya pertanyaan "kenapa laporan ini jatuh ke pihak itu"
 * bisa dijawab dari data, bukan dari menebak ulang logika waterfall
 * dengan kondisi wilayah yang mungkin sudah berubah sejak saat itu.
 */
enum AlasanRouting: string implements HasLabel
{
    use ProvidesOptions;

    case KabupatenTerverifikasi = 'kabupaten_terverifikasi';
    case ProvinsiTerverifikasi = 'provinsi_terverifikasi';
    case DesaTerverifikasi = 'desa_terverifikasi';
    case WilayahBelumTerjangkau = 'wilayah_belum_terjangkau';

    public function label(): string
    {
        return match ($this) {
            self::KabupatenTerverifikasi => 'Kabupaten/kota sudah terverifikasi',
            self::ProvinsiTerverifikasi => 'Provinsi sudah terverifikasi, kabupaten/kota belum',
            self::DesaTerverifikasi => 'Desa sudah terverifikasi, kabupaten/kota dan provinsi belum',
            self::WilayahBelumTerjangkau => 'Belum ada wilayah terverifikasi di jalur ini',
        };
    }

    /** Tingkat wilayah yang jadi dasar keputusan; null untuk wilayah belum terjangkau. */
    public function tingkat(): ?TingkatWilayah
    {
        return match ($this) {
            self::KabupatenTerverifikasi => TingkatWilayah::Kabupaten,
            self::ProvinsiTerverifikasi => TingkatWilayah::Provinsi,
            self::DesaTerverifikasi => TingkatWilayah::Desa,
            self::WilayahBelumTerjangkau => null,
        };
    }

    /** Jenis penanggung jawab yang dihasilkan alasan ini. */
    public function penanggungJawab(): PenanggungJawabType
    {
        return match ($this) {
            self::KabupatenTerverifikasi => PenanggungJawabType::AdminKabupaten,
            self::ProvinsiTerverifikasi => PenanggungJawabType::AdminProvinsi,
            self::DesaTerverifikasi => PenanggungJawabType::KepalaDesa,
            self::WilayahBelumTerjangkau => PenanggungJawabType::FasilitatorWilayah,
        };
    }

    /** Laporan dengan alasan ini menaikkan wilayah.skor_prioritas (CLAUDE.md 9.4). */
    public function menaikkanSkorPrioritas(): bool
    {
        return $this === self::WilayahBelumTerjangkau;
    }
}
