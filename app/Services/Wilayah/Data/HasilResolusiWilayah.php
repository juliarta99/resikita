<?php

declare(strict_types=1);

namespace App\Services\Wilayah\Data;

use App\Models\Wilayah;

/**
 * Hasil penyelesaian sepasang koordinat menjadi empat id wilayah.
 *
 * Keempatnya disimpan sekaligus ke baris `laporan`. Objek ini yang
 * dilewatkan antar Service supaya tidak ada yang perlu menebak urutan
 * elemen sebuah array.
 */
final readonly class HasilResolusiWilayah
{
    public function __construct(
        public ?int $desaId = null,
        public ?int $kecamatanId = null,
        public ?int $kabupatenId = null,
        public ?int $provinsiId = null,
        public ?float $jarakKm = null,
    ) {}

    /** Objek kosong untuk koordinat yang tidak jatuh di wilayah mana pun. */
    public static function kosong(): self
    {
        return new self;
    }

    /**
     * Susun dari sebuah simpul wilayah dengan menelusuri rantai induknya
     * ke atas. Simpul yang dilewatkan boleh berada di tingkat mana pun.
     */
    public static function dariSimpul(Wilayah $simpul, ?float $jarakKm = null): self
    {
        $ids = [];

        $sekarang = $simpul;

        while ($sekarang !== null) {
            $ids[$sekarang->tingkat->value] = $sekarang->id;
            $sekarang = $sekarang->parent;
        }

        return new self(
            desaId: $ids['desa'] ?? null,
            kecamatanId: $ids['kecamatan'] ?? null,
            kabupatenId: $ids['kabupaten'] ?? null,
            provinsiId: $ids['provinsi'] ?? null,
            jarakKm: $jarakKm,
        );
    }

    /** Tidak satu pun tingkat berhasil ditentukan. */
    public function tidakDitemukan(): bool
    {
        return $this->desaId === null
            && $this->kecamatanId === null
            && $this->kabupatenId === null
            && $this->provinsiId === null;
    }

    /** @return array{desa_id: ?int, kecamatan_id: ?int, kabupaten_id: ?int, provinsi_id: ?int} */
    public function toKolomLaporan(): array
    {
        return [
            'desa_id' => $this->desaId,
            'kecamatan_id' => $this->kecamatanId,
            'kabupaten_id' => $this->kabupatenId,
            'provinsi_id' => $this->provinsiId,
        ];
    }
}
