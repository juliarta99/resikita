<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WasteDepositItem extends Model
{
    protected $fillable = ['deposit_id', 'waste_price_id', 'berat', 'harga_snapshot', 'subtotal'];

    public function deposit(): BelongsTo
    {
        return $this->belongsTo(WasteDeposit::class, 'deposit_id');
    }

    public function wastePrice(): BelongsTo
    {
        return $this->belongsTo(WastePrice::class, 'waste_price_id');
    }
}
