<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StatusAktif;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankSampah extends Model
{
    use HasFactory;

    protected $table = 'bank_sampah';

    protected $fillable = [
        'nama',
        'alamat',
        'latitude',
        'longitude',
        'phone',
        'email',
        'foto',
        'wilayah_id',
        'jam_operasional',
        'status',
        'is_verified',
    ];

    protected function casts(): array
    {
        return [
            'status' => StatusAktif::class,
            'is_verified' => 'boolean',
            'jam_operasional' => 'array',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function wilayah(): BelongsTo
    {
        return $this->belongsTo(Wilayah::class);
    }

    /** Pengguna berrole bank_sampah yang mengelola unit ini. */
    public function pengelola(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function harga(): HasMany
    {
        return $this->hasMany(BankSampahHarga::class);
    }

    public function setoran(): HasMany
    {
        return $this->hasMany(SetoranSampah::class);
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status', StatusAktif::Aktif);
    }

    public function scopeTerverifikasi(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }
}
