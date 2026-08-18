<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StatusSetoran;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SetoranSampah extends Model
{
    use HasFactory;

    protected $table = 'setoran_sampah';

    protected $fillable = [
        'bank_sampah_id',
        'petugas_id',
        'nasabah_id',
        'kode_setoran',
        'total_berat',
        'total_nilai',
        'status',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'status' => StatusSetoran::class,
            'total_berat' => 'decimal:2',
            'total_nilai' => 'integer',
        ];
    }

    public function bankSampah(): BelongsTo
    {
        return $this->belongsTo(BankSampah::class);
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    public function nasabah(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nasabah_id');
    }

    public function item(): HasMany
    {
        return $this->hasMany(SetoranSampahItem::class, 'setoran_id');
    }

    public function scopeSelesai(Builder $query): Builder
    {
        return $query->where('status', StatusSetoran::Selesai);
    }
}
