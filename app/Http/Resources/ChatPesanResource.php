<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ChatPesan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ChatPesan
 */
class ChatPesanResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'role' => $this->role->value,
            'konten' => $this->konten,
            'sumber_input' => $this->sumber_input?->value,
            'dibacakan' => $this->dibacakan,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
