<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Report extends Model
{
    protected $fillable = ['pelapor_id', 'kategori_id', 'tiket_no', 'judul', 'deskripsi', 'foto', 'lat', 'lng', 'alamat', 'banjar_id', 'status', 'is_duplikat', 'duplikat_of_id', 'verified_by'];

    protected function casts(): array
    {
        return [
            'is_duplikat' => 'boolean',
        ];
    }

    public function pelapor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pelapor_id');
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(ReportCategory::class, 'kategori_id');
    }

    public function banjarDinas(): BelongsTo
    {
        return $this->belongsTo(BanjarDinas::class, 'banjar_id');
    }

    public function duplikatOf(): BelongsTo
    {
        return $this->belongsTo(Report::class, 'duplikat_of_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ReportAssignment::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(ReportProgress::class);
    }
}
