<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Ulasan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Ulasan
 */
class UlasanResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'komentar' => $this->komentar,
            'foto_url' => $this->urlFoto(),
            'penulis' => new PenggunaRingkasResource($this->whenLoaded('user')),
            'produk_id' => $this->produk_id,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
