<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Sesi percakapan dengan asisten literasi lingkungan.
 *
 * `wilayah_konteks_id` menyimpan wilayah yang dipakai sebagai konteks
 * lokal. Nama daerah tidak pernah ditanam di prompt dasar, konteks
 * disisipkan per sesi dari kolom ini.
 */
class ChatSesi extends Model
{
    use HasFactory;

    protected $table = 'chat_sesi';

    protected $fillable = ['user_id', 'judul', 'wilayah_konteks_id', 'terakhir_at'];

    protected function casts(): array
    {
        return ['terakhir_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wilayahKonteks(): BelongsTo
    {
        return $this->belongsTo(Wilayah::class, 'wilayah_konteks_id');
    }

    public function pesan(): HasMany
    {
        return $this->hasMany(ChatPesan::class, 'sesi_id')->orderBy('created_at');
    }
}
