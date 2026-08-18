<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\SetoranSampah;
use App\Models\SetoranSampahItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SetoranSampah
 */
class SetoranSampahResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kode_setoran' => $this->kode_setoran,
            'total_berat' => (float) $this->total_berat,
            'total_nilai' => $this->total_nilai,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'catatan' => $this->catatan,

            'bank_sampah' => new BankSampahResource($this->whenLoaded('bankSampah')),
            'nasabah' => new PenggunaRingkasResource($this->whenLoaded('nasabah')),
            'petugas' => new PenggunaRingkasResource($this->whenLoaded('petugas')),

            // Nilai snapshot yang dikirim, bukan harga katalog saat ini.
            // Riwayat setoran harus menampilkan angka yang berlaku waktu
            // itu, apa pun yang terjadi pada katalog sesudahnya.
            'item' => $this->whenLoaded('item', fn () => $this->item->map(fn (SetoranSampahItem $i): array => [
                'id' => $i->id,
                'jenis' => $i->jenis_snapshot,
                'berat' => (float) $i->berat,
                'harga_per_satuan' => $i->harga_snapshot,
                'subtotal' => $i->subtotal,
            ])->values()),

            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
