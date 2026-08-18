<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\LaporanKategori;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LaporanKategori
 */
class LaporanKategoriResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'deskripsi' => $this->deskripsi,
            'ikon' => $this->ikon,
        ];
    }
}
