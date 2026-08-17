<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\BankSampah;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin BankSampah
 */
class BankSampahResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'alamat' => $this->alamat,
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            'phone' => $this->phone,
            'email' => $this->email,
            'foto_url' => $this->foto !== null ? Storage::url($this->foto) : null,
            'jam_operasional' => $this->jam_operasional,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_verified' => $this->is_verified,
            'wilayah' => new WilayahResource($this->whenLoaded('wilayah')),
            'jumlah_jenis_harga' => $this->whenCounted('harga'),

            // Diisi oleh query pencarian terdekat, tidak selalu ada.
            'jarak_km' => $this->when(
                isset($this->jarak_km),
                fn (): float => round((float) $this->jarak_km, 2),
            ),
        ];
    }
}
