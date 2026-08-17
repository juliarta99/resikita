<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MetodeBayar;
use App\Enums\StatusPesanan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Pesanan extends Model
{
    use HasFactory;

    protected $table = 'pesanan';

    protected $fillable = [
        'kode',
        'user_id',
        'umkm_id',
        'subtotal',
        'ongkir',
        'total',
        'metode_bayar',
        'status',
        'nama_penerima',
        'phone_penerima',
        'alamat_kirim',
        'destination_id',
        'kurir',
        'layanan_kurir',
        'no_resi',
        'snap_token',
        'dibayar_at',
        'dikirim_at',
        'selesai_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => StatusPesanan::class,
            'metode_bayar' => MetodeBayar::class,
            'subtotal' => 'integer',
            'ongkir' => 'integer',
            'total' => 'integer',
            'destination_id' => 'integer',
            'dibayar_at' => 'datetime',
            'dikirim_at' => 'datetime',
            'selesai_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function umkm(): BelongsTo
    {
        return $this->belongsTo(Umkm::class);
    }

    public function item(): HasMany
    {
        return $this->hasMany(PesananItem::class);
    }

    public function pembayaran(): MorphMany
    {
        return $this->morphMany(Pembayaran::class, 'payable');
    }

    public function ulasan(): HasMany
    {
        return $this->hasMany(Ulasan::class);
    }

    public function scopeBerstatus(Builder $query, StatusPesanan $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeUntukDaftar(Builder $query): Builder
    {
        return $query->with(['umkm', 'item.produk:id,nama,slug']);
    }

    public function getRouteKeyName(): string
    {
        return 'kode';
    }
}
