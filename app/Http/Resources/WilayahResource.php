<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Wilayah;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Wilayah
 */
class WilayahResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kode' => $this->kode,
            'nama' => $this->nama,
            'nama_lengkap' => $this->namaLengkap(),
            'tingkat' => $this->tingkat->value,
            'tingkat_label' => $this->tingkat->label(),
            'status_registrasi' => $this->status_registrasi->value,
            'status_label' => $this->status_registrasi->label(),
            'terverifikasi' => $this->isTerverifikasi(),
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            'parent' => new self($this->whenLoaded('parent')),
        ];
    }
}
