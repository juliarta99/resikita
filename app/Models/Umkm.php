<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StatusUmkm;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Umkm extends Model
{
    use HasFactory;

    protected $table = 'umkm';

    protected $fillable = [
        'nama',
        'deskripsi',
        'alamat',
        'latitude',
        'longitude',
        'phone',
        'email',
        'foto',
        'wilayah_id',

        // Titik asal pengiriman milik toko ini. `destination_id` adalah id
        // wilayah penyedia ongkir, sama seperti `pesanan.destination_id`,
        // hanya berada di ujung yang berlawanan. `alamat_asal` menyimpan
        // label yang dipilih penjual supaya pilihannya bisa ditampilkan
        // balik tanpa memanggil penyedia lagi.
        'destination_id',
        'alamat_asal',

        'status',
        'is_verified',

        // Jejak peninjauan admin. `catatan_verifikasi` dibaca pemilik
        // toko di halaman status pendaftarannya, jadi isinya ditujukan
        // kepadanya, bukan catatan internal antar-admin.
        'catatan_verifikasi',
        'ditinjau_oleh',
        'ditinjau_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => StatusUmkm::class,
            'is_verified' => 'boolean',
            'destination_id' => 'integer',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'ditinjau_at' => 'datetime',
        ];
    }

    public function wilayah(): BelongsTo
    {
        return $this->belongsTo(Wilayah::class);
    }

    public function pengelola(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function produk(): HasMany
    {
        return $this->hasMany(Produk::class);
    }

    public function pesanan(): HasMany
    {
        return $this->hasMany(Pesanan::class);
    }

    public function ulasan(): HasMany
    {
        return $this->hasMany(Ulasan::class);
    }

    public function dompet(): HasOne
    {
        return $this->hasOne(UmkmDompet::class);
    }

    public function penarikan(): HasMany
    {
        return $this->hasMany(UmkmPenarikan::class);
    }

    public function kontenPromosi(): HasMany
    {
        return $this->hasMany(KontenPromosi::class);
    }

    public function peninjau(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditinjau_oleh');
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status', StatusUmkm::Aktif);
    }

    public function scopeMenunggu(Builder $query): Builder
    {
        return $query->where('status', StatusUmkm::Menunggu);
    }

    /** Toko sudah lolos peninjauan dan boleh membuka panel penjual. */
    public function bolehBerjualan(): bool
    {
        return $this->status->bolehBerjualan();
    }

    /** Pendaftarannya ditolak dan menunggu perbaikan dari pemiliknya. */
    public function ditolak(): bool
    {
        return $this->status === StatusUmkm::Ditolak;
    }
}
