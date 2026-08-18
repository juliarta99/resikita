<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\BankSampahHarga;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin BankSampahHarga
 */
class BankSampahHargaResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'bank_sampah_id' => $this->bank_sampah_id,
            'jenis_sampah' => $this->jenis_sampah,
            'kategori' => $this->kategori->value,
            'kategori_label' => $this->kategori->label(),
            'satuan' => $this->satuan,
            // Rupiah penuh sebagai integer, pemformatan di sisi klien.
            'harga_per_satuan' => $this->harga_per_satuan,
            'is_active' => $this->is_active,
            'bank_sampah' => new BankSampahResource($this->whenLoaded('bankSampah')),
        ];
    }
}
