<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PlatformPerangkat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerangkatToken extends Model
{
    use HasFactory;

    protected $table = 'perangkat_token';

    protected $fillable = ['user_id', 'token', 'platform', 'terakhir_aktif_at'];

    protected $hidden = ['token'];

    protected function casts(): array
    {
        return [
            'platform' => PlatformPerangkat::class,
            'terakhir_aktif_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
