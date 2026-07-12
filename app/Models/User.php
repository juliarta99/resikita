<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'nik',
        'tanggal_lahir',
        'jenis_kelamin',
        'nip',
        'email',
        'phone',
        'phone_verified_at',
        'password',
        'kode_qr',
        'is_active',
        'kecamatan_id',
        'kelurahan_id',
        'banjar_id',
        'tps_id',
        'bank_sampah_id',
        'umkm_id',
        'lat',
        'lng',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'tanggal_lahir'     => 'date',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class);
    }

    public function kelurahan(): BelongsTo
    {
        return $this->belongsTo(Kelurahan::class);
    }

    public function banjarDinas(): BelongsTo
    {
        return $this->belongsTo(BanjarDinas::class, 'banjar_id');
    }

    public function tps(): BelongsTo
    {
        return $this->belongsTo(Tps::class);
    }

    public function bankSampah(): BelongsTo
    {
        return $this->belongsTo(BankSampah::class);
    }

    public function umkm(): BelongsTo
    {
        return $this->belongsTo(Umkm::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }
}