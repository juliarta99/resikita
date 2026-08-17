<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\JenisTps;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tps extends Model
{
    use HasFactory;

    protected $table = 'tps';

    protected $fillable = [
        'nama',
        'alamat',
        'latitude',
        'longitude',
        'phone',
        'foto',
        'jenis',
        'is_berbayar',
        'tarif_bulanan',
        'wilayah_id',
        'kapasitas_ton',
    ];

    protected function casts(): array
    {
        return [
            'jenis' => JenisTps::class,
            'is_berbayar' => 'boolean',
            'tarif_bulanan' => 'integer',
            'kapasitas_ton' => 'decimal:2',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function wilayah(): BelongsTo
    {
        return $this->belongsTo(Wilayah::class);
    }

    public function anggota(): HasMany
    {
        return $this->hasMany(TpsAnggota::class);
    }

    public function scopeTps3r(Builder $query): Builder
    {
        return $query->where('jenis', JenisTps::Tps3r);
    }

    public function scopeBerbayar(Builder $query): Builder
    {
        return $query->where('is_berbayar', true);
    }
}
