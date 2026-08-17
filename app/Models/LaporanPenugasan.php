<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StatusPenugasan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaporanPenugasan extends Model
{
    use HasFactory;

    protected $table = 'laporan_penugasan';

    protected $fillable = [
        'laporan_id',
        'petugas_id',
        'ditugaskan_oleh',
        'status',
        'ditugaskan_at',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'status' => StatusPenugasan::class,
            'ditugaskan_at' => 'datetime',
        ];
    }

    public function laporan(): BelongsTo
    {
        return $this->belongsTo(Laporan::class);
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    public function penugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditugaskan_oleh');
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->whereIn('status', [
            StatusPenugasan::Ditugaskan,
            StatusPenugasan::Dikerjakan,
        ]);
    }

    public function scopeMilikPetugas(Builder $query, int $petugasId): Builder
    {
        return $query->where('petugas_id', $petugasId);
    }
}
