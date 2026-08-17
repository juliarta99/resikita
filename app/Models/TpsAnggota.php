<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StatusAktif;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TpsAnggota extends Model
{
    use HasFactory;

    protected $table = 'tps_anggota';

    protected $fillable = ['tps_id', 'user_id', 'status', 'bergabung_at'];

    protected function casts(): array
    {
        return [
            'status' => StatusAktif::class,
            'bergabung_at' => 'datetime',
        ];
    }

    public function tps(): BelongsTo
    {
        return $this->belongsTo(Tps::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function iuran(): HasMany
    {
        return $this->hasMany(TpsIuran::class, 'tps_anggota_id');
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status', StatusAktif::Aktif);
    }
}
