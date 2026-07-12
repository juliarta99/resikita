<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BanjarDinas extends Model
{
    protected $table = 'banjar_dinas';

    protected $fillable = ['kelurahan_id', 'nama'];

    public function kelurahan(): BelongsTo
    {
        return $this->belongsTo(Kelurahan::class);
    }
}
