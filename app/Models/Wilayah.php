<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StatusRegistrasiWilayah;
use App\Enums\TingkatWilayah;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Satu simpul hierarki wilayah administrasi.
 *
 * Model ini hanya menyediakan relasi dan scope. Semua penalaran tentang
 * wilayah, resolusi koordinat, pembatasan cakupan role, penentuan
 * penanggung jawab, ada di App\Services\Wilayah, bukan di sini.
 */
class Wilayah extends Model
{
    use HasFactory;

    protected $table = 'wilayah';

    protected $fillable = [
        'kode',
        'nama',
        'tingkat',
        'parent_id',
        'latitude',
        'longitude',
        'status_registrasi',
        'skor_prioritas',
        'terverifikasi_at',
    ];

    protected function casts(): array
    {
        return [
            'tingkat' => TingkatWilayah::class,
            'status_registrasi' => StatusRegistrasiWilayah::class,
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'skor_prioritas' => 'integer',
            'terverifikasi_at' => 'datetime',
        ];
    }

    // ----------------------------------------------------------------
    // Relasi
    // ----------------------------------------------------------------

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function pengajuan(): HasMany
    {
        return $this->hasMany(PengajuanWilayah::class);
    }

    public function penduduk(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function bankSampah(): HasMany
    {
        return $this->hasMany(BankSampah::class);
    }

    public function tps(): HasMany
    {
        return $this->hasMany(Tps::class);
    }

    public function umkm(): HasMany
    {
        return $this->hasMany(Umkm::class);
    }

    /** Laporan yang jatuh di wilayah ini, pada tingkat mana pun ia berada. */
    public function laporan(): HasMany
    {
        $kolom = match ($this->tingkat) {
            TingkatWilayah::Provinsi => 'provinsi_id',
            TingkatWilayah::Kabupaten => 'kabupaten_id',
            TingkatWilayah::Kecamatan => 'kecamatan_id',
            TingkatWilayah::Desa => 'desa_id',
        };

        return $this->hasMany(Laporan::class, $kolom);
    }

    // ----------------------------------------------------------------
    // Scope
    // ----------------------------------------------------------------

    public function scopeTingkat(Builder $query, TingkatWilayah $tingkat): Builder
    {
        return $query->where('tingkat', $tingkat);
    }

    public function scopeTerverifikasi(Builder $query): Builder
    {
        return $query->where('status_registrasi', StatusRegistrasiWilayah::Terverifikasi);
    }

    public function scopeBelumTerjangkau(Builder $query): Builder
    {
        return $query->where('status_registrasi', StatusRegistrasiWilayah::BelumTerjangkau);
    }

    /** Papan prioritas perluasan: wilayah dengan laporan terbanyak lebih dulu. */
    public function scopePrioritasTertinggi(Builder $query): Builder
    {
        return $query->orderByDesc('skor_prioritas')->orderBy('nama');
    }

    // ----------------------------------------------------------------
    // Pembantu tampilan
    // ----------------------------------------------------------------

    public function isTerverifikasi(): bool
    {
        return $this->status_registrasi === StatusRegistrasiWilayah::Terverifikasi;
    }

    /** Nama lengkap dengan sebutan tingkatnya, mis. "Kabupaten Badung". */
    public function namaLengkap(): string
    {
        return match ($this->tingkat) {
            TingkatWilayah::Provinsi => 'Provinsi '.$this->nama,
            TingkatWilayah::Kecamatan => 'Kecamatan '.$this->nama,
            default => $this->nama,
        };
    }
}
