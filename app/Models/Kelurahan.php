<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kelurahan extends Model
{
    protected $table = 'kelurahan';

    protected $fillable = ['kecamatan_id', 'nama'];

    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class);
    }

    public function banjarDinas(): HasMany
    {
        return $this->hasMany(BanjarDinas::class);
    }
}
