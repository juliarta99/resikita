<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TujuanOtp;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtpToken extends Model
{
    use HasFactory;

    protected $table = 'otp_token';

    protected $fillable = [
        'user_id',
        'tujuan',
        'kode_hash',
        'expires_at',
        'verified_at',
        'used_at',
    ];

    protected $hidden = ['kode_hash'];

    protected function casts(): array
    {
        return [
            'tujuan' => TujuanOtp::class,
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Kode yang belum dipakai dan belum kedaluwarsa. */
    public function scopeBerlaku(Builder $query): Builder
    {
        return $query->whereNull('used_at')->where('expires_at', '>', now());
    }

    public function scopeUntuk(Builder $query, TujuanOtp $tujuan): Builder
    {
        return $query->where('tujuan', $tujuan);
    }

    public function sudahKedaluwarsa(): bool
    {
        return $this->expires_at->isPast();
    }

    public function sudahDipakai(): bool
    {
        return $this->used_at !== null;
    }
}
