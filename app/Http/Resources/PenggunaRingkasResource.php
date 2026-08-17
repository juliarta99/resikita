<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Pengguna dilihat oleh orang lain: pelapor sebuah laporan, nasabah di
 * layar petugas, penulis ulasan.
 *
 * Sengaja tipis. Email, nomor telepon, tanggal lahir, dan kode QR tidak
 * pernah ikut, tidak satu pun konteks di atas membutuhkannya, dan
 * membawa data pribadi yang tidak dibutuhkan adalah cara paling mudah
 * membocorkannya tanpa sadar.
 *
 * @mixin User
 */
class PenggunaRingkasResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'avatar_url' => $this->avatar_path !== null ? Storage::url($this->avatar_path) : null,
        ];
    }
}
