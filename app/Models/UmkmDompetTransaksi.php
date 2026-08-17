<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TipeTransaksiDompet;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UmkmDompetTransaksi extends Model
{
    use HasFactory;

    protected $table = 'umkm_dompet_transaksi';

    protected $fillable = [
        'umkm_dompet_id',
        'tipe',
        'jumlah',
        'saldo_sebelum',
        'saldo_sesudah',
        'reference_type',
        'reference_id',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tipe' => TipeTransaksiDompet::class,
            'jumlah' => 'integer',
            'saldo_sebelum' => 'integer',
            'saldo_sesudah' => 'integer',
        ];
    }

    public function dompet(): BelongsTo
    {
        return $this->belongsTo(UmkmDompet::class, 'umkm_dompet_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo('reference');
    }
}
