<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\TpsIuran;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TpsIuran
 */
class TpsIuranResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'periode' => $this->periode,
            'jumlah' => $this->jumlah,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_warna' => $this->status->warna(),
            'bisa_dibayar' => $this->status->bisaDibayar(),
            'metode_bayar' => $this->metode_bayar?->value,
            'dibayar_at' => $this->dibayar_at?->toIso8601String(),
            'tps' => new TpsResource($this->whenLoaded('anggota', fn () => $this->anggota->tps ?? null)),
        ];
    }
}
