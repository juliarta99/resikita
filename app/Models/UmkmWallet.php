<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UmkmWallet extends Model
{
    protected $fillable = ['umkm_id', 'saldo'];
    protected $casts = ['saldo' => 'decimal:2'];

    public function umkm(): BelongsTo { return $this->belongsTo(Umkm::class); }
    public function transactions(): HasMany { return $this->hasMany(UmkmWalletTransaction::class); }
}