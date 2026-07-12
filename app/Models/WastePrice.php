<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WastePrice extends Model
{
    protected $fillable = ['jenis_sampah', 'satuan', 'harga_per_kg', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
