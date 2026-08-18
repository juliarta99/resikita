<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Foto produk asli milik UMKM.
 *
 * Asisten Konten tidak pernah menimpa baris ini. Hasil komposisi
 * sampul disimpan terpisah di konten_promosi.sampul_path, foto produk
 * yang dilihat pembeli harus tetap foto barang yang sebenarnya.
 */
class ProdukFoto extends Model
{
    use HasFactory;

    protected $table = 'produk_foto';

    protected $fillable = ['produk_id', 'path', 'urutan', 'is_utama'];

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'is_utama' => 'boolean',
        ];
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class);
    }

    public function url(): string
    {
        return Storage::url($this->path);
    }
}
