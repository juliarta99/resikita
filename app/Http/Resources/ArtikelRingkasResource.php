<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Artikel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Kartu artikel untuk daftar. Konten penuh diganti cuplikan.
 *
 * @mixin Artikel
 */
class ArtikelRingkasResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'judul' => $this->judul,
            'slug' => $this->slug,
            'tipe' => $this->tipe->value,
            'tipe_label' => $this->tipe->label(),
            // Cuplikan diambil dari teks_baca yang sudah bersih markdown,
            // supaya kartu tidak menampilkan tanda pagar dan bintang.
            'cuplikan' => Str::limit(strip_tags($this->teks_baca ?? $this->konten), 160),
            'estimasi_baca_menit' => $this->estimasi_baca_menit,
            'thumbnail_url' => $this->thumbnail !== null ? Storage::url($this->thumbnail) : null,
            'dilihat' => $this->dilihat,
            'is_unggulan' => $this->is_unggulan,
            'kategori' => $this->whenLoaded('kategori', fn (): ?string => $this->kategori?->nama),
            'published_at' => $this->published_at?->toIso8601String(),
        ];
    }
}
