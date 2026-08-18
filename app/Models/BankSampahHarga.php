<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\KategoriSampah;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Satu baris katalog harga milik sebuah bank sampah. */
class BankSampahHarga extends Model
{
    use HasFactory;

    protected $table = 'bank_sampah_harga';

    protected $fillable = [
        'bank_sampah_id',
        'jenis_sampah',
        'kategori',
        'satuan',
        'harga_per_satuan',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'kategori' => KategoriSampah::class,
            'harga_per_satuan' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function bankSampah(): BelongsTo
    {
        return $this->belongsTo(BankSampah::class);
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
