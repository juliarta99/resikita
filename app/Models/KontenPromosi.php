<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NadaKonten;
use App\Enums\TujuanKonten;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Keluaran Asisten Konten UMKM.
 *
 * `is_ai_generated` selalu true dan labelnya wajib tampil ke pengguna.
 * Pembeli berhak tahu bahwa teks atau sampul yang dilihatnya disusun
 * dengan bantuan AI.
 */
class KontenPromosi extends Model
{
    use HasFactory;

    protected $table = 'konten_promosi';

    protected $fillable = [
        'umkm_id',
        'produk_id',
        'tujuan',
        'nada',
        'hasil_teks',
        'hasil_hashtag',
        'sampul_path',
        'preferensi_sampul',
        'is_ai_generated',
        'model_version',
        'raw_response',
        'dipakai',
    ];

    protected function casts(): array
    {
        return [
            'tujuan' => TujuanKonten::class,
            'nada' => NadaKonten::class,
            'hasil_hashtag' => 'array',
            'preferensi_sampul' => 'array',
            'is_ai_generated' => 'boolean',
            'raw_response' => 'array',
            'dipakai' => 'boolean',
        ];
    }

    public function umkm(): BelongsTo
    {
        return $this->belongsTo(Umkm::class);
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class);
    }

    public function scopeTujuan(Builder $query, TujuanKonten $tujuan): Builder
    {
        return $query->where('tujuan', $tujuan);
    }

    public function scopeDipakai(Builder $query): Builder
    {
        return $query->where('dipakai', true);
    }

    public function urlSampul(): ?string
    {
        return $this->sampul_path !== null ? Storage::url($this->sampul_path) : null;
    }
}
