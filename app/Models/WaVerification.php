<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaVerification extends Model
{
    protected $fillable = ['phone', 'kode', 'expires_at', 'verified_at'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }
}
