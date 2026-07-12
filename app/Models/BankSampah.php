<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankSampah extends Model
{
    protected $table = 'bank_sampah';

    protected $fillable = ['nama', 'alamat', 'lat', 'lng', 'no_hp', 'foto', 'banjar_id'];

    public function banjarDinas(): BelongsTo
    {
        return $this->belongsTo(BanjarDinas::class, 'banjar_id');
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(WasteDeposit::class, 'bank_sampah_id');
    }
}
