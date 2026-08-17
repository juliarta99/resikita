<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\KategoriSampah;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class KlasifikasiSampah extends Model
{
    use HasFactory;

    protected $table = 'klasifikasi_sampah';

    protected $fillable = [
        'user_id',
        'foto_path',
        'jenis',
        'kategori',
        'material',
        'confidence',
        'dapat_didaur_ulang',
        'estimasi_berat_kg',
        'estimasi_nilai',
        'langkah_pengolahan',
        'rekomendasi_daur_ulang',
        'catatan',
        'model_version',
        'raw_response',
    ];

    protected function casts(): array
    {
        return [
            'kategori' => KategoriSampah::class,
            'confidence' => 'decimal:2',
            'dapat_didaur_ulang' => 'boolean',
            'estimasi_berat_kg' => 'decimal:3',
            'estimasi_nilai' => 'integer',
            'langkah_pengolahan' => 'array',
            'raw_response' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeKategori(Builder $query, KategoriSampah $kategori): Builder
    {
        return $query->where('kategori', $kategori);
    }

    public function urlFoto(): string
    {
        return Storage::url($this->foto_path);
    }

    /**
     * Hasil dengan keyakinan rendah sebaiknya ditampilkan dengan
     * peringatan, bukan sebagai kepastian.
     */
    public function keyakinanRendah(): bool
    {
        return (float) $this->confidence < 60;
    }
}
