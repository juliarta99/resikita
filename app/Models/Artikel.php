<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StatusArtikel;
use App\Enums\TipeArtikel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Materi literasi lingkungan.
 *
 * `teks_baca` diisi oleh App\Services\Konten\TeksBacaService saat
 * artikel disimpan, bukan dibersihkan ulang di klien. Web dan mobile
 * membacakan teks yang identik karena keduanya membaca kolom yang sama.
 */
class Artikel extends Model
{
    use HasFactory;

    protected $table = 'artikel';

    protected $fillable = [
        'penulis_id',
        'kategori_id',
        'tipe',
        'judul',
        'slug',
        'konten',
        'teks_baca',
        'estimasi_baca_menit',
        'thumbnail',
        'video_url',
        'status',
        'dilihat',
        'didengarkan',
        'is_unggulan',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'tipe' => TipeArtikel::class,
            'status' => StatusArtikel::class,
            'dilihat' => 'integer',
            'didengarkan' => 'integer',
            'estimasi_baca_menit' => 'integer',
            'is_unggulan' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function penulis(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penulis_id');
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(ArtikelKategori::class, 'kategori_id');
    }

    public function scopeTerbit(Builder $query): Builder
    {
        return $query
            ->where('status', StatusArtikel::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeUnggulan(Builder $query): Builder
    {
        return $query->where('is_unggulan', true);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** Naikkan penghitung tanpa menyentuh updated_at. */
    public function catatDilihat(): void
    {
        $this->incrementQuietly('dilihat');
    }

    public function catatDidengarkan(): void
    {
        $this->incrementQuietly('didengarkan');
    }
}
