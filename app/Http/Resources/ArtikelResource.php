<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Artikel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Artikel lengkap.
 *
 * `teks_baca` tidak ikut di sini meski kolomnya ada. Isinya bisa
 * sepanjang seluruh artikel, dan hanya dibutuhkan ketika pemutar suara
 * benar-benar dinyalakan, endpoint `/artikel/{slug}/teks-baca` yang
 * melayaninya. Menyertakannya di setiap pembacaan artikel berarti
 * menggandakan muatan untuk fitur yang tidak selalu dipakai.
 *
 * @mixin Artikel
 */
class ArtikelResource extends JsonResource
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
            'konten' => $this->konten,
            'estimasi_baca_menit' => $this->estimasi_baca_menit,
            'thumbnail_url' => $this->thumbnail !== null ? Storage::url($this->thumbnail) : null,
            'video_url' => $this->video_url,
            'dilihat' => $this->dilihat,
            'didengarkan' => $this->didengarkan,
            'is_unggulan' => $this->is_unggulan,
            'kategori' => $this->whenLoaded('kategori', fn (): ?array => $this->kategori === null ? null : [
                'id' => $this->kategori->id,
                'nama' => $this->kategori->nama,
                'slug' => $this->kategori->slug,
            ]),
            'penulis' => new PenggunaRingkasResource($this->whenLoaded('penulis')),
            'published_at' => $this->published_at?->toIso8601String(),
        ];
    }
}
