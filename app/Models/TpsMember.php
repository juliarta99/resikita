<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TpsMember extends Model
{
    protected $fillable = ['tps_id', 'user_id', 'status', 'joined_at'];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
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

    public function subscriptions(): HasMany
    {
        return $this->hasMany(TpsSubscription::class, 'tps_member_id');
    }
}
