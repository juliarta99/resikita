<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Tps;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin Tps
 */
class TpsResource extends JsonResource
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
            'foto_url' => $this->foto !== null ? Storage::url($this->foto) : null,

            'jenis' => $this->jenis->value,
            'jenis_label' => $this->jenis->label(),
            // Pembedaan TPS dan TPS3R ikut dijelaskan, bukan hanya
            // dilabeli: warga perlu tahu ke mana sampah terpilahnya
            // benar-benar berguna.
            'jenis_deskripsi' => $this->jenis->deskripsi(),
            'mengolah_di_tempat' => $this->jenis->mengolahDiTempat(),

            'is_berbayar' => $this->is_berbayar,
            'tarif_bulanan' => $this->tarif_bulanan,
            'kapasitas_ton' => $this->kapasitas_ton !== null ? (float) $this->kapasitas_ton : null,

            'wilayah' => new WilayahResource($this->whenLoaded('wilayah')),
            'jumlah_anggota' => $this->whenCounted('anggota'),

            'jarak_km' => $this->when(
                isset($this->jarak_km),
                fn (): float => round((float) $this->jarak_km, 2),
            ),
        ];
    }
}
