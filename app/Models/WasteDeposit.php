<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WasteDeposit extends Model
{
    protected $fillable = ['bank_sampah_id', 'petugas_id', 'nasabah_id', 'total_berat', 'total_nilai', 'status'];

    public function bankSampah(): BelongsTo
    {
        return $this->belongsTo(BankSampah::class, 'bank_sampah_id');
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    public function nasabah(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nasabah_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(WasteDepositItem::class, 'deposit_id');
    }
}
