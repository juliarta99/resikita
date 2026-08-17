<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Dompet saldo masyarakat.
 *
 * Saldo tidak pernah diubah langsung lewat model ini. Semua mutasi
 * lewat App\Services\Wallet\DompetService supaya `saldo_sebelum` dan
 * `saldo_sesudah` di tabel mutasi selalu terisi konsisten dan seluruh
 * perubahan terbungkus transaction.
 */
class Dompet extends Model
{
    use HasFactory;

    protected $table = 'dompet';

    protected $fillable = ['user_id', 'saldo'];

    protected function casts(): array
    {
        return ['saldo' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transaksi(): HasMany
    {
        return $this->hasMany(DompetTransaksi::class);
    }

    public function cukup(int $jumlah): bool
    {
        return $this->saldo >= $jumlah;
    }
}
