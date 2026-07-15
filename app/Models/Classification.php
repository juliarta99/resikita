<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Classification extends Model
{
    protected $fillable = [
        'user_id', 'foto', 'hasil_jenis', 'deskripsi', 'kategori', 'material',
        'confidence', 'dapat_didaur_ulang', 'nilai_jual', 'estimasi_berat',
        'langkah_pengolahan', 'rekomendasi', 'catatan',
    ];

    protected function casts(): array
    {
        return [
            'dapat_didaur_ulang' => 'boolean',
            'confidence'         => 'float',
            'nilai_jual'         => 'float',
            'estimasi_berat'     => 'float',
            'langkah_pengolahan' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}