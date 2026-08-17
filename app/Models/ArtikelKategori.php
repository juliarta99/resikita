<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArtikelKategori extends Model
{
    use HasFactory;

    protected $table = 'artikel_kategori';

    protected $fillable = ['nama', 'slug'];

    public function artikel(): HasMany
    {
        return $this->hasMany(Artikel::class, 'kategori_id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
