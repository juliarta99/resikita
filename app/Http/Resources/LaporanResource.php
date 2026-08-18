<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Laporan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin Laporan
 */
class LaporanResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tiket' => $this->tiket,
            'judul' => $this->judul,
            'deskripsi' => $this->deskripsi,
            'deskripsi_sumber' => $this->deskripsi_sumber->value,

            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_warna' => $this->status->warna(),

            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'alamat' => $this->alamat,

            'kategori' => new LaporanKategoriResource($this->whenLoaded('kategori')),
            'pelapor' => new PenggunaRingkasResource($this->whenLoaded('pelapor')),

            'wilayah' => [
                'desa' => new WilayahResource($this->whenLoaded('desa')),
                'kecamatan' => new WilayahResource($this->whenLoaded('kecamatan')),
                'kabupaten' => new WilayahResource($this->whenLoaded('kabupaten')),
                'provinsi' => new WilayahResource($this->whenLoaded('provinsi')),
            ],

            // Jejak audit routing ikut dikirim supaya pelapor bisa
            // melihat siapa yang memegang laporannya dan atas dasar apa,
            // termasuk ketika jawabannya "wilayah Anda belum bergabung".
            'penanggung_jawab' => [
                'tipe' => $this->penanggung_jawab_type?->value,
                'tipe_label' => $this->penanggung_jawab_type?->label(),
                'alasan' => $this->alasan_routing?->value,
                'alasan_label' => $this->alasan_routing?->label(),
                'butuh_pendampingan' => $this->alasan_routing?->menaikkanSkorPrioritas() ?? false,
            ],

            'is_duplikat' => $this->is_duplikat,
            'duplikat_of_id' => $this->duplikat_of_id,
            'jumlah_gabungan' => $this->whenCounted('duplikat'),

            'foto' => $this->whenLoaded('foto', fn () => $this->foto->map(fn ($f): array => [
                'id' => $f->id,
                'url' => Storage::url($f->path),
                'urutan' => $f->urutan,
            ])->values()),

            'progres' => LaporanProgresResource::collection($this->whenLoaded('progres')),
            'penugasan' => LaporanPenugasanResource::collection($this->whenLoaded('penugasan')),

            'waktu_respons_jam' => $this->when(
                $this->selesai_at !== null,
                fn (): ?float => $this->waktuResponsJam(),
            ),

            'diverifikasi_at' => $this->diverifikasi_at?->toIso8601String(),
            'selesai_at' => $this->selesai_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
