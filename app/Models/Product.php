<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = ['umkm_id', 'kategori_id', 'nama', 'deskripsi', 'harga', 'stok', 'berat', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function umkm(): BelongsTo
    {
        return $this->belongsTo(Umkm::class);
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'kategori_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }
}
