<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\DompetTransaksi;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DompetTransaksi
 */
class DompetTransaksiResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipe' => $this->tipe->value,
            'tipe_label' => $this->tipe->label(),
            'is_pemasukan' => $this->tipe->isPemasukan(),
            'jumlah' => $this->jumlah,
            'saldo_sebelum' => $this->saldo_sebelum,
            'saldo_sesudah' => $this->saldo_sesudah,
            'keterangan' => $this->keterangan,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
