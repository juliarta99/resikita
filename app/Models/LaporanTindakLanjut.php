<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Catatan kontak Fasilitator Wilayah ke dinas atas sebuah laporan. */
class LaporanTindakLanjut extends Model
{
    use HasFactory;

    protected $table = 'laporan_tindak_lanjut';

    protected $fillable = [
        'laporan_id',
        'fasilitator_id',
        'nama_dinas',
        'kontak_dinas',
        'tanggal_kontak',
        'hasil',
        'lampiran_path',
    ];

    protected function casts(): array
    {
        return ['tanggal_kontak' => 'date'];
    }

    public function laporan(): BelongsTo
    {
        return $this->belongsTo(Laporan::class);
    }

    public function fasilitator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fasilitator_id');
    }
}
