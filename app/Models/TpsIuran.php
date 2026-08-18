<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MetodeBayar;
use App\Enums\StatusIuran;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class TpsIuran extends Model
{
    use HasFactory;

    protected $table = 'tps_iuran';

    protected $fillable = [
        'tps_anggota_id',
        'periode',
        'jumlah',
        'status',
        'metode_bayar',
        'dibayar_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => StatusIuran::class,
            'metode_bayar' => MetodeBayar::class,
            'jumlah' => 'integer',
            'dibayar_at' => 'datetime',
        ];
    }

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(TpsAnggota::class, 'tps_anggota_id');
    }

    public function pembayaran(): MorphMany
    {
        return $this->morphMany(Pembayaran::class, 'payable');
    }

    public function scopeBelumLunas(Builder $query): Builder
    {
        return $query->where('status', '!=', StatusIuran::Lunas);
    }

    public function scopePeriode(Builder $query, string $periode): Builder
    {
        return $query->where('periode', $periode);
    }
}
