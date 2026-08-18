<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LaporanKategori extends Model
{
    use HasFactory;

    protected $table = 'laporan_kategori';

    protected $fillable = ['nama', 'deskripsi', 'ikon', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function laporan(): HasMany
    {
        return $this->hasMany(Laporan::class, 'kategori_id');
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
