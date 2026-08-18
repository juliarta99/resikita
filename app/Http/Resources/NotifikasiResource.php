<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Notifikasi;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Notifikasi
 */
class NotifikasiResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipe' => $this->tipe,
            'judul' => $this->judul,
            'pesan' => $this->pesan,
            'action_url' => $this->action_url,
            'channel' => $this->channel->value,
            'status' => $this->status->value,
            'dibaca' => $this->dibaca_at !== null,
            'dibaca_at' => $this->dibaca_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
