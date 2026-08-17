<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Profil pengguna untuk pemiliknya sendiri.
 *
 * Perhatikan yang tidak ada: NIK dan NIP tidak pernah dikembalikan
 * karena kolomnya memang tidak ada (CLAUDE.md 4.2). `kode_qr` ikut
 * dikirim karena pemiliknya perlu menampilkannya di bank sampah;
 * untuk melihat pengguna lain, pakai PenggunaRingkasResource.
 *
 * @mixin User
 */
class UserResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_terverifikasi' => $this->email_verified_at !== null,
            'phone' => $this->phone,
            'phone_terverifikasi' => $this->phone_verified_at !== null,
            'avatar_url' => $this->avatar_path !== null ? Storage::url($this->avatar_path) : null,
            'tanggal_lahir' => $this->tanggal_lahir?->toDateString(),
            'jenis_kelamin' => $this->jenis_kelamin?->value,
            'kode_qr' => $this->kode_qr,
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            'is_active' => $this->is_active,

            'role' => $this->roleUtama()?->value,
            'role_label' => $this->roleUtama()?->label(),
            'permissions' => $this->getAllPermissions()->pluck('name')->values(),

            'wilayah' => new WilayahResource($this->whenLoaded('wilayah')),
            'bank_sampah_id' => $this->bank_sampah_id,
            'umkm_id' => $this->umkm_id,

            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
