<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Pesanan;
use App\Models\PesananItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Pesanan
 */
class PesananResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kode' => $this->kode,

            'subtotal' => $this->subtotal,
            'ongkir' => $this->ongkir,
            'total' => $this->total,

            'metode_bayar' => $this->metode_bayar->value,
            'metode_bayar_label' => $this->metode_bayar->label(),

            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_warna' => $this->status->warna(),

            // Aturan perpindahan status dikirim sebagai kemampuan, bukan
            // dihitung ulang di klien. Kalau aturannya berubah, mobile
            // ikut berubah tanpa perlu dirilis ulang.
            'bisa_dibatalkan' => $this->status->bisaDibatalkanPembeli(),
            'bisa_diulas' => $this->status->bisaDiulas(),

            'nama_penerima' => $this->nama_penerima,
            'phone_penerima' => $this->phone_penerima,
            'alamat_kirim' => $this->alamat_kirim,
            'kurir' => $this->kurir,
            'layanan_kurir' => $this->layanan_kurir,
            'no_resi' => $this->no_resi,

            // Snap token hanya berguna selama pesanan belum dibayar.
            'snap_token' => $this->when(
                $this->status->value === 'menunggu_bayar',
                fn (): ?string => $this->snap_token,
            ),

            'umkm' => new UmkmResource($this->whenLoaded('umkm')),

            'item' => $this->whenLoaded('item', fn () => $this->item->map(fn (PesananItem $i): array => [
                'id' => $i->id,
                'produk_id' => $i->produk_id,
                // Nilai snapshot: nota yang sudah terbit tidak ikut
                // berubah ketika penjual mengubah harga atau nama.
                'nama' => $i->nama_snapshot,
                'harga' => $i->harga_snapshot,
                'qty' => $i->qty,
                'subtotal' => $i->subtotal,
            ])->values()),

            'dibayar_at' => $this->dibayar_at?->toIso8601String(),
            'dikirim_at' => $this->dikirim_at?->toIso8601String(),
            'selesai_at' => $this->selesai_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
