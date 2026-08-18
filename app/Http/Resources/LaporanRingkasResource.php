<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Laporan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Bentuk ringkas untuk daftar dan kartu laporan.
 *
 * Deskripsi lengkap, riwayat progres, dan rantai wilayah sengaja tidak
 * ikut. Daftar laporan bisa memuat ratusan baris sekaligus, dan
 * membawa semuanya ke jaringan seluler adalah pemborosan yang paling
 * terasa justru di tempat sinyalnya paling buruk.
 *
 * @mixin Laporan
 */
class LaporanRingkasResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tiket' => $this->tiket,
            'judul' => $this->judul,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_warna' => $this->status->warna(),
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'alamat' => $this->alamat,
            'kategori' => new LaporanKategoriResource($this->whenLoaded('kategori')),
            'pelapor' => new PenggunaRingkasResource($this->whenLoaded('pelapor')),
            'desa' => $this->whenLoaded('desa', fn (): ?string => $this->desa?->nama),
            'kabupaten' => $this->whenLoaded('kabupaten', fn (): ?string => $this->kabupaten?->nama),
            'foto_utama' => $this->whenLoaded(
                'foto',
                fn (): ?string => $this->foto->first() !== null ? Storage::url($this->foto->first()->path) : null,
            ),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
