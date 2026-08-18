<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Umkm;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin Umkm
 */
class UmkmResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'deskripsi' => $this->deskripsi,
            'alamat' => $this->alamat,
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            'phone' => $this->phone,
            'email' => $this->email,
            'foto_url' => $this->foto !== null ? Storage::url($this->foto) : null,

            // Dari mana paket toko ini berangkat. Bukan sekadar hiasan:
            // ongkir yang berbeda antar toko dalam satu keranjang jadi
            // masuk akal begitu pembeli melihat asalnya masing-masing.
            'asal_pengiriman' => $this->alamat_asal,

            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_verified' => $this->is_verified,
            'wilayah' => new WilayahResource($this->whenLoaded('wilayah')),
            'jumlah_produk' => $this->whenCounted('produk'),
            'rating_rata' => $this->when(
                isset($this->rating_rata),
                fn (): ?float => $this->rating_rata !== null ? round((float) $this->rating_rata, 1) : null,
            ),
            'jumlah_ulasan' => $this->whenCounted('ulasan'),
        ];
    }
}
