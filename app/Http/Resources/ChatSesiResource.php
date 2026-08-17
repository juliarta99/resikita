<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ChatSesi;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ChatSesi
 */
class ChatSesiResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'judul' => $this->judul,
            'wilayah_konteks' => $this->whenLoaded(
                'wilayahKonteks',
                fn (): ?string => $this->wilayahKonteks?->namaLengkap(),
            ),
            'jumlah_pesan' => $this->whenCounted('pesan'),
            'pesan' => ChatPesanResource::collection($this->whenLoaded('pesan')),
            'terakhir_at' => $this->terakhir_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
