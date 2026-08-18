<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LogAktivitas extends Model
{
    use HasFactory;

    protected $table = 'log_aktivitas';

    protected $fillable = [
        'user_id',
        'aksi',
        'subject_type',
        'subject_id',
        'deskripsi',
        'ip_address',
        'user_agent',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeAksi(Builder $query, string $aksi): Builder
    {
        return $query->where('aksi', $aksi);
    }
}
