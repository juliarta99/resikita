<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\PenarikanSaldo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @mixin PenarikanSaldo
 */
class PenarikanSaldoResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'jumlah' => $this->jumlah,
            'metode' => $this->metode,
            'nama_bank' => $this->nama_bank,
            // Nomor rekening disamarkan sebagian. Riwayat penarikan
            // sering dibuka di tempat umum, dan pemiliknya cukup melihat
            // empat angka terakhir untuk mengenali rekeningnya sendiri.
            'no_rekening' => $this->samarkanRekening($this->no_rekening),
            'atas_nama' => $this->atas_nama,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_warna' => $this->status->warna(),
            'catatan' => $this->catatan,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    private function samarkanRekening(string $nomor): string
    {
        return Str::length($nomor) <= 4
            ? $nomor
            : str_repeat('*', Str::length($nomor) - 4).Str::substr($nomor, -4);
    }
}
