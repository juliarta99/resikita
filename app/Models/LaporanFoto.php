<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class LaporanFoto extends Model
{
    use HasFactory;

    protected $table = 'laporan_foto';

    protected $fillable = ['laporan_id', 'path', 'urutan'];

    protected function casts(): array
    {
        return ['urutan' => 'integer'];
    }

    public function laporan(): BelongsTo
    {
        return $this->belongsTo(Laporan::class);
    }

    public function url(): string
    {
        return Storage::url($this->path);
    }
}
