<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Produk;
use App\Models\ProdukFoto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Produk
 */
class ProdukResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'slug' => $this->slug,
            'deskripsi' => $this->deskripsi,
            'harga' => $this->harga,
            'stok' => $this->stok,
            'tersedia' => $this->is_active && $this->stok > 0,
            'berat_gram' => $this->berat_gram,

            // Asal bahan adalah bagian dari nilai jual produk di
            // marketplace ini, bukan keterangan tambahan.
            'bahan_baku' => $this->bahan_baku,

            'kategori' => $this->whenLoaded('kategori', fn (): array => [
                'id' => $this->kategori->id,
                'nama' => $this->kategori->nama,
                'slug' => $this->kategori->slug,
            ]),

            'umkm' => new UmkmResource($this->whenLoaded('umkm')),

            'foto_utama_url' => $this->whenLoaded('fotoUtama', fn (): ?string => $this->fotoUtama?->url()),
            'foto' => $this->whenLoaded('foto', fn () => $this->foto->map(fn (ProdukFoto $f): array => [
                'id' => $f->id,
                'url' => $f->url(),
                'is_utama' => $f->is_utama,
            ])->values()),

            'rating_rata' => $this->when(
                isset($this->rating_rata),
                fn (): ?float => $this->rating_rata !== null ? round((float) $this->rating_rata, 1) : null,
            ),
            'jumlah_ulasan' => $this->whenCounted('ulasan'),
        ];
    }
}
