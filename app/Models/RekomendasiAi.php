<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class RekomendasiAi extends Model
{
    use HasFactory;

    protected $table = 'rekomendasi_ai';

    protected $fillable = [
        'scope_type',
        'scope_id',
        'periode',
        'konten',
        'raw_response',
        'dibuat_oleh',
    ];

    protected function casts(): array
    {
        return ['raw_response' => 'array'];
    }

    public function scope(): MorphTo
    {
        return $this->morphTo('scope');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function scopePeriode(Builder $query, string $periode): Builder
    {
        return $query->where('periode', $periode);
    }
}
