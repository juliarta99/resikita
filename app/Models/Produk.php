<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Produk extends Model
{
    use HasFactory;

    protected $table = 'produk';

    protected $fillable = [
        'umkm_id',
        'kategori_id',
        'nama',
        'slug',
        'deskripsi',
        'harga',
        'stok',
        'berat_gram',
        'bahan_baku',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'harga' => 'integer',
            'stok' => 'integer',
            'berat_gram' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function umkm(): BelongsTo
    {
        return $this->belongsTo(Umkm::class);
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(ProdukKategori::class, 'kategori_id');
    }

    public function foto(): HasMany
    {
        return $this->hasMany(ProdukFoto::class)->orderBy('urutan');
    }

    public function fotoUtama(): HasOne
    {
        return $this->hasOne(ProdukFoto::class)->where('is_utama', true);
    }

    public function ulasan(): HasMany
    {
        return $this->hasMany(Ulasan::class);
    }

    public function kontenPromosi(): HasMany
    {
        return $this->hasMany(KontenPromosi::class);
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeTersedia(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('stok', '>', 0);
    }

    /**
     * Muat relasi dan agregat yang selalu dipakai kartu produk,
     * supaya daftar produk tidak memicu N+1.
     *
     * `umkm` dimuat utuh, bukan beberapa kolom saja. ProdukResource
     * merender relasi ini lewat UmkmResource, dan UmkmResource membaca
     * `status` yang bertipe enum, kolom yang tidak ikut terpilih
     * bernilai null, lalu `null->value` melempar galat dan seluruh
     * endpoint daftar produk mati.
     *
     * Jangan dipersempit lagi demi performa: tabel `umkm` adalah data
     * rujukan berukuran kecil, dan selisih beberapa kolom di sini tidak
     * sebanding dengan matinya satu endpoint.
     */
    public function scopeUntukKatalog(Builder $query): Builder
    {
        return $query
            ->with(['umkm', 'kategori:id,nama,slug', 'fotoUtama'])
            ->withAvg('ulasan as rating_rata', 'rating')
            ->withCount('ulasan');
    }

    public function stokCukup(int $qty): bool
    {
        return $this->stok >= $qty;
    }

    /**
     * Produk dirujuk lewat slug, bukan id auto-increment.
     *
     * Sama seperti artikel. Dua alasan: alamat halaman produk di
     * marketplace publik ikut menyebut namanya, dan id berurutan yang
     * terbuka memberi tahu siapa pun berapa banyak produk yang ada di
     * seluruh platform beserta laju penambahannya.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
