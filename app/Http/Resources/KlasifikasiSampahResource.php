<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\KlasifikasiSampah;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Hasil klasifikasi sampah oleh AI.
 *
 * `keyakinan_rendah` dikirim eksplisit supaya aplikasi mobile tidak
 * perlu menghitung ambang batasnya sendiri, kalau tiap klien memilih
 * ambangnya masing-masing, hasil yang sama akan ditampilkan sebagai
 * kepastian di satu tempat dan sebagai dugaan di tempat lain.
 *
 * `raw_response` tidak pernah ikut. Isinya jejak audit untuk peladen,
 * bukan konsumsi klien.
 *
 * @mixin KlasifikasiSampah
 */
class KlasifikasiSampahResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'foto_url' => $this->urlFoto(),
            'jenis' => $this->jenis,
            'kategori' => $this->kategori->value,
            'kategori_label' => $this->kategori->label(),
            'kategori_deskripsi' => $this->kategori->deskripsi(),
            'kategori_warna' => $this->kategori->warna(),
            'butuh_penanganan_khusus' => $this->kategori->butuhPenangananKhusus(),
            'material' => $this->material,
            'confidence' => (float) $this->confidence,
            'keyakinan_rendah' => $this->keyakinanRendah(),
            'dapat_didaur_ulang' => $this->dapat_didaur_ulang,
            'estimasi_berat_kg' => $this->estimasi_berat_kg !== null ? (float) $this->estimasi_berat_kg : null,
            'estimasi_nilai' => $this->estimasi_nilai,
            'langkah_pengolahan' => $this->langkah_pengolahan,
            'rekomendasi_daur_ulang' => $this->rekomendasi_daur_ulang,
            'catatan' => $this->catatan,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
