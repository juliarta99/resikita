<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SetoranSampahItem extends Model
{
    use HasFactory;

    protected $table = 'setoran_sampah_item';

    protected $fillable = [
        'setoran_id',
        'harga_id',
        'jenis_snapshot',
        'berat',
        'harga_snapshot',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'berat' => 'decimal:2',
            'harga_snapshot' => 'integer',
            'subtotal' => 'integer',
        ];
    }

    public function setoran(): BelongsTo
    {
        return $this->belongsTo(SetoranSampah::class, 'setoran_id');
    }

    public function harga(): BelongsTo
    {
        return $this->belongsTo(BankSampahHarga::class, 'harga_id');
    }
}
