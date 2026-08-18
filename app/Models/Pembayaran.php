<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StatusPembayaran;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Pembayaran extends Model
{
    use HasFactory;

    protected $table = 'pembayaran';

    protected $fillable = [
        'payable_type',
        'payable_id',
        'metode',
        'midtrans_order_id',
        'midtrans_transaction_id',
        'jumlah',
        'status',
        'dibayar_at',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'status' => StatusPembayaran::class,
            'jumlah' => 'integer',
            'dibayar_at' => 'datetime',
            'raw_payload' => 'array',
        ];
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeLunas(Builder $query): Builder
    {
        return $query->where('status', StatusPembayaran::Paid);
    }
}
