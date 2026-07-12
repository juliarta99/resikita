<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TpsSubscription extends Model
{
    protected $fillable = ['tps_member_id', 'periode', 'jumlah', 'status', 'metode_bayar', 'paid_at'];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(TpsMember::class, 'tps_member_id');
    }
}
