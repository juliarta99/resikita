<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UmkmWalletTransaction extends Model
{
    protected $fillable = ['umkm_wallet_id', 'tipe', 'jumlah', 'saldo_after', 'reference_type', 'reference_id', 'keterangan'];
    protected $casts = ['jumlah' => 'decimal:2', 'saldo_after' => 'decimal:2'];

    public function wallet(): BelongsTo { return $this->belongsTo(UmkmWallet::class, 'umkm_wallet_id'); }
}