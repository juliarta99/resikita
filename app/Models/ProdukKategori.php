<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProdukKategori extends Model
{
    use HasFactory;

    protected $table = 'produk_kategori';

    protected $fillable = ['nama', 'slug', 'ikon'];

    public function produk(): HasMany
    {
        return $this->hasMany(Produk::class, 'kategori_id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
