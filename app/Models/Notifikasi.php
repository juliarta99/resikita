<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ChannelNotifikasi;
use App\Enums\StatusNotifikasi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notifikasi extends Model
{
    use HasFactory;

    protected $table = 'notifikasi';

    protected $fillable = [
        'user_id',
        'tipe',
        'channel',
        'judul',
        'pesan',
        'action_url',
        'status',
        'provider_ref',
        'dibaca_at',
    ];

    protected function casts(): array
    {
        return [
            'channel' => ChannelNotifikasi::class,
            'status' => StatusNotifikasi::class,
            'dibaca_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeBelumDibaca(Builder $query): Builder
    {
        return $query->whereNull('dibaca_at');
    }

    public function tandaiDibaca(): void
    {
        if ($this->dibaca_at === null) {
            $this->update([
                'dibaca_at' => now(),
                'status' => StatusNotifikasi::Dibaca,
            ]);
        }
    }
}
