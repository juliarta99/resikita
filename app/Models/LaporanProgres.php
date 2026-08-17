<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StatusProgres;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class LaporanProgres extends Model
{
    use HasFactory;

    protected $table = 'laporan_progres';

    protected $fillable = [
        'laporan_id',
        'petugas_id',
        'catatan',
        'foto_bukti',
        'status_progres',
        'latitude',
        'longitude',
    ];

    protected function casts(): array
    {
        return [
            'status_progres' => StatusProgres::class,
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
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

    public function urlFotoBukti(): ?string
    {
        return $this->foto_bukti !== null ? Storage::url($this->foto_bukti) : null;
    }
}
