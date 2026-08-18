<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Dompet UMKM.
 *
 * Dimiliki oleh badan usaha, bukan oleh orang. Satu UMKM bisa berganti
 * pengelola tanpa saldonya ikut berpindah tangan.
 */
class UmkmDompet extends Model
{
    use HasFactory;

    protected $table = 'umkm_dompet';

    protected $fillable = ['umkm_id', 'saldo'];

    protected function casts(): array
    {
        return ['saldo' => 'integer'];
    }

    public function umkm(): BelongsTo
    {
        return $this->belongsTo(Umkm::class);
    }

    public function transaksi(): HasMany
    {
        return $this->hasMany(UmkmDompetTransaksi::class);
    }

    public function cukup(int $jumlah): bool
    {
        return $this->saldo >= $jumlah;
    }
}
