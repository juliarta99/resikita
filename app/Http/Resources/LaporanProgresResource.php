<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\LaporanProgres;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LaporanProgres
 */
class LaporanProgresResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'catatan' => $this->catatan,
            'foto_bukti_url' => $this->urlFotoBukti(),
            'status_progres' => $this->status_progres->value,
            'status_label' => $this->status_progres->label(),
            'petugas' => new PenggunaRingkasResource($this->whenLoaded('petugas')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
