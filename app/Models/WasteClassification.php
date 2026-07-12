<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WasteClassification extends Model
{
    protected $fillable = ['user_id', 'image_path', 'hasil_jenis', 'kategori', 'confidence', 'langkah_pengolahan', 'rekomendasi_daur_ulang', 'raw_response'];

    protected function casts(): array
    {
        return [
            'langkah_pengolahan' => 'array',
            'raw_response' => 'array',
            'confidence' => 'decimal:3',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
