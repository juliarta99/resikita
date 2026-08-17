<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StatusPengajuanWilayah;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengajuanWilayah extends Model
{
    use HasFactory;

    protected $table = 'pengajuan_wilayah';

    protected $fillable = [
        'wilayah_id',
        'pemohon_nama',
        'pemohon_jabatan',
        'pemohon_email',
        'pemohon_phone',
        'instansi',
        'surat_path',
        'status',
        'catatan',
        'ditinjau_oleh',
        'ditinjau_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => StatusPengajuanWilayah::class,
            'ditinjau_at' => 'datetime',
        ];
    }

    public function wilayah(): BelongsTo
    {
        return $this->belongsTo(Wilayah::class);
    }

    public function peninjau(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditinjau_oleh');
    }

    public function scopeMenunggu(Builder $query): Builder
    {
        return $query->where('status', StatusPengajuanWilayah::Diajukan);
    }

    public function sudahDitinjau(): bool
    {
        return $this->status->sudahFinal();
    }
}
