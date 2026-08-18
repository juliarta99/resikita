<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\PengajuanWilayah;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Berkas pengajuan pendaftaran wilayah.
 *
 * `surat_path` tidak dikembalikan sebagai URL terbuka. Surat tugas
 * memuat identitas dan jabatan pejabat; berkasnya hanya boleh dibuka
 * peninjau lewat endpoint terautentikasi, bukan lewat tautan yang bisa
 * diteruskan siapa saja.
 *
 * @mixin PengajuanWilayah
 */
class PengajuanWilayahResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'wilayah' => new WilayahResource($this->whenLoaded('wilayah')),
            'pemohon_nama' => $this->pemohon_nama,
            'pemohon_jabatan' => $this->pemohon_jabatan,
            'pemohon_email' => $this->pemohon_email,
            'pemohon_phone' => $this->pemohon_phone,
            'instansi' => $this->instansi,
            'ada_surat' => $this->surat_path !== null,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_warna' => $this->status->warna(),
            'catatan' => $this->catatan,
            'ditinjau_at' => $this->ditinjau_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
