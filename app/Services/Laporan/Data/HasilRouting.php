<?php

declare(strict_types=1);

namespace App\Services\Laporan\Data;

use App\Enums\AlasanRouting;
use App\Enums\PenanggungJawabType;

/**
 * Keputusan waterfall penanggung jawab beserta alasannya.
 *
 * Alasan dibawa bersama hasilnya, bukan disimpulkan belakangan, supaya
 * jejak auditnya selalu konsisten dengan keputusan yang benar-benar
 * diambil saat itu.
 */
final readonly class HasilRouting
{
    public function __construct(
        public PenanggungJawabType $type,
        public ?int $userId,
        public AlasanRouting $alasan,
        public ?int $wilayahId = null,
    ) {}

    /** @return array{penanggung_jawab_type: string, penanggung_jawab_id: ?int, alasan_routing: string} */
    public function toKolomLaporan(): array
    {
        return [
            'penanggung_jawab_type' => $this->type->value,
            'penanggung_jawab_id' => $this->userId,
            'alasan_routing' => $this->alasan->value,
        ];
    }

    /**
     * Laporan jatuh ke Fasilitator Wilayah, artinya belum ada
     * pemerintah terverifikasi di jalur wilayah ini.
     */
    public function butuhPendampingan(): bool
    {
        return $this->alasan === AlasanRouting::WilayahBelumTerjangkau;
    }

    /**
     * Keputusan sudah diambil tapi tidak ada akun yang bisa dituju.
     * Laporan tetap tercatat dan tetap punya jejak alasan, tapi perlu
     * perhatian admin karena tak seorang pun menerimanya.
     */
    public function tanpaPenerima(): bool
    {
        return $this->userId === null;
    }
}
