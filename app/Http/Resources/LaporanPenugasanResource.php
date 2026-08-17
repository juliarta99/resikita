<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\LaporanPenugasan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LaporanPenugasan
 */
class LaporanPenugasanResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'catatan' => $this->catatan,
            'ditugaskan_at' => $this->ditugaskan_at?->toIso8601String(),
            'petugas' => new PenggunaRingkasResource($this->whenLoaded('petugas')),
            'laporan' => new LaporanRingkasResource($this->whenLoaded('laporan')),
        ];
    }
}
