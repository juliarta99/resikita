<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AlasanRouting;
use App\Enums\PenanggungJawabType;
use App\Enums\StatusLaporan;
use App\Enums\SumberInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Laporan sampah dari masyarakat.
 *
 * Model ini tidak menentukan penanggung jawab, tidak mencari duplikat,
 * dan tidak menyelesaikan koordinat menjadi wilayah. Semuanya di
 * App\Services\Laporan dan App\Services\Wilayah, supaya web dan mobile
 * memakai aturan yang sama persis.
 */
class Laporan extends Model
{
    use HasFactory;

    protected $table = 'laporan';

    protected $fillable = [
        'tiket',
        'pelapor_id',
        'kategori_id',
        'judul',
        'deskripsi',
        'deskripsi_sumber',
        'latitude',
        'longitude',
        'alamat',
        'desa_id',
        'kecamatan_id',
        'kabupaten_id',
        'provinsi_id',
        'penanggung_jawab_type',
        'penanggung_jawab_id',
        'alasan_routing',
        'status',
        'is_duplikat',
        'duplikat_of_id',
        'diverifikasi_oleh',
        'diverifikasi_at',
        'selesai_at',
    ];

    protected function casts(): array
    {
        return [
            'deskripsi_sumber' => SumberInput::class,
            'status' => StatusLaporan::class,
            'alasan_routing' => AlasanRouting::class,
            'penanggung_jawab_type' => PenanggungJawabType::class,
            'is_duplikat' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'diverifikasi_at' => 'datetime',
            'selesai_at' => 'datetime',
        ];
    }

    // ----------------------------------------------------------------
    // Relasi
    // ----------------------------------------------------------------

    public function pelapor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pelapor_id');
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(LaporanKategori::class, 'kategori_id');
    }

    public function desa(): BelongsTo
    {
        return $this->belongsTo(Wilayah::class, 'desa_id');
    }

    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Wilayah::class, 'kecamatan_id');
    }

    public function kabupaten(): BelongsTo
    {
        return $this->belongsTo(Wilayah::class, 'kabupaten_id');
    }

    public function provinsi(): BelongsTo
    {
        return $this->belongsTo(Wilayah::class, 'provinsi_id');
    }

    public function penanggungJawab(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penanggung_jawab_id');
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }

    public function duplikatDari(): BelongsTo
    {
        return $this->belongsTo(self::class, 'duplikat_of_id');
    }

    public function duplikat(): HasMany
    {
        return $this->hasMany(self::class, 'duplikat_of_id');
    }

    public function foto(): HasMany
    {
        return $this->hasMany(LaporanFoto::class)->orderBy('urutan');
    }

    public function penugasan(): HasMany
    {
        return $this->hasMany(LaporanPenugasan::class);
    }

    public function progres(): HasMany
    {
        return $this->hasMany(LaporanProgres::class)->latest();
    }

    public function tindakLanjut(): HasMany
    {
        return $this->hasMany(LaporanTindakLanjut::class);
    }

    // ----------------------------------------------------------------
    // Scope
    // ----------------------------------------------------------------

    public function scopeAktif(Builder $query): Builder
    {
        return $query->whereIn('status', StatusLaporan::aktif());
    }

    public function scopeBerstatus(Builder $query, StatusLaporan $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Laporan yang menunggu tindakan penanggung jawabnya.
     *
     * Bukan sekadar "aktif": laporan yang sudah ditugaskan atau sedang
     * dikerjakan berada di tangan petugas, bukan di tangan pemerintah
     * wilayah. Yang tersisa di meja pemerintah adalah laporan baru yang
     * belum diverifikasi dan laporan terverifikasi yang belum diberi
     * petugas, dua keadaan itulah yang benar-benar mandek kalau
     * didiamkan.
     */
    public function scopeMenungguTindakan(Builder $query): Builder
    {
        return $query->whereIn('status', [StatusLaporan::Baru, StatusLaporan::Diverifikasi]);
    }

    /** Laporan yang menunggu pendampingan Fasilitator Wilayah. */
    public function scopeBelumTerjangkau(Builder $query): Builder
    {
        return $query->where('alasan_routing', AlasanRouting::WilayahBelumTerjangkau);
    }

    /** Muat relasi yang hampir selalu ikut ditampilkan, agar tidak N+1. */
    public function scopeUntukDaftar(Builder $query): Builder
    {
        return $query->with([
            'kategori:id,nama,ikon,deskripsi',
            'pelapor:id,name,avatar_path',
            'desa:id,nama',
            'kabupaten:id,nama',
            'foto',
        ]);
    }

    // ----------------------------------------------------------------
    // Pembantu tampilan
    // ----------------------------------------------------------------

    /** Selisih waktu masuk sampai selesai, dalam jam. Null kalau belum selesai. */
    public function waktuResponsJam(): ?float
    {
        if ($this->selesai_at === null) {
            return null;
        }

        return round($this->created_at->diffInMinutes($this->selesai_at) / 60, 1);
    }

    public function fotoUtama(): ?LaporanFoto
    {
        return $this->foto->first();
    }
}
