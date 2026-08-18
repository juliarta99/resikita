<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StatusPenarikan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenarikanSaldo extends Model
{
    use HasFactory;

    protected $table = 'penarikan_saldo';

    protected $fillable = [
        'user_id',
        'jumlah',
        'metode',
        'nama_bank',
        'no_rekening',
        'atas_nama',
        'status',
        'disetujui_oleh',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'status' => StatusPenarikan::class,
            'jumlah' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function penyetuju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }

    public function scopeMenunggu(Builder $query): Builder
    {
        return $query->where('status', StatusPenarikan::Menunggu);
    }
}
