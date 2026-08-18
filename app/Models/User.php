<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\JenisKelamin;
use App\Enums\Role as RoleEnum;
use App\Enums\TingkatWilayah;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * Pengguna Resikita.
 *
 * Identitas utama adalah email. NIK dan NIP tidak ada di sini dan tidak
 * boleh ditambahkan kembali, alasannya dijabarkan di CLAUDE.md 4.2.
 *
 * `wilayah_id` punya dua makna bergantung role: domisili untuk
 * masyarakat, cakupan kewenangan untuk role pemerintahan dan petugas.
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * Guard yang dipakai Spatie untuk mencari role dan permission.
     *
     * Wajib disematkan. Tanpa ini, Spatie memakai guard yang sedang
     * aktif: permintaan web mencari permission bertanda `web`,
     * permintaan bertoken mencari yang bertanda `sanctum`. Karena
     * matriks kewenangan hanya disemai sekali pada guard `web`, seluruh
     * pemeriksaan izin di kanal API akan menjawab "tidak boleh",
     * dan menjawabnya dengan tenang, tanpa galat, sehingga kelihatan
     * seperti masalah otorisasi biasa.
     *
     * Menyemai dua set kewenangan bisa saja dilakukan, tapi itu
     * menciptakan dua sumber kebenaran yang bisa menyimpang diam-diam.
     * Menyematkan satu guard di sini membuat web dan mobile dijamin
     * memakai matriks yang sama persis.
     */
    protected $guard_name = 'web';

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'phone_verified_at',
        'email_verified_at',
        'avatar_path',
        'tanggal_lahir',
        'jenis_kelamin',
        'kode_qr',
        'wilayah_id',
        'bank_sampah_id',
        'umkm_id',
        'latitude',
        'longitude',
        'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'tanggal_lahir' => 'date',
            'jenis_kelamin' => JenisKelamin::class,
            'password' => 'hashed',
            'is_active' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    // ----------------------------------------------------------------
    // Relasi
    // ----------------------------------------------------------------

    public function wilayah(): BelongsTo
    {
        return $this->belongsTo(Wilayah::class);
    }

    public function bankSampah(): BelongsTo
    {
        return $this->belongsTo(BankSampah::class);
    }

    public function umkm(): BelongsTo
    {
        return $this->belongsTo(Umkm::class);
    }

    public function dompet(): HasOne
    {
        return $this->hasOne(Dompet::class);
    }

    public function otpTokens(): HasMany
    {
        return $this->hasMany(OtpToken::class);
    }

    public function laporan(): HasMany
    {
        return $this->hasMany(Laporan::class, 'pelapor_id');
    }

    /** Laporan yang dipegang pengguna ini sebagai penanggung jawab wilayah. */
    public function laporanDitangani(): HasMany
    {
        return $this->hasMany(Laporan::class, 'penanggung_jawab_id');
    }

    public function penugasan(): HasMany
    {
        return $this->hasMany(LaporanPenugasan::class, 'petugas_id');
    }

    public function klasifikasi(): HasMany
    {
        return $this->hasMany(KlasifikasiSampah::class);
    }

    public function setoran(): HasMany
    {
        return $this->hasMany(SetoranSampah::class, 'nasabah_id');
    }

    public function penarikan(): HasMany
    {
        return $this->hasMany(PenarikanSaldo::class);
    }

    public function keanggotaanTps(): HasMany
    {
        return $this->hasMany(TpsAnggota::class);
    }

    public function keranjang(): HasMany
    {
        return $this->hasMany(Keranjang::class);
    }

    public function pesanan(): HasMany
    {
        return $this->hasMany(Pesanan::class);
    }

    public function ulasan(): HasMany
    {
        return $this->hasMany(Ulasan::class);
    }

    public function chatSesi(): HasMany
    {
        return $this->hasMany(ChatSesi::class);
    }

    public function notifikasi(): HasMany
    {
        return $this->hasMany(Notifikasi::class);
    }

    public function perangkatToken(): HasMany
    {
        return $this->hasMany(PerangkatToken::class);
    }

    // ----------------------------------------------------------------
    // Scope
    // ----------------------------------------------------------------

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeDenganRole(Builder $query, RoleEnum $role): Builder
    {
        return $query->role($role->value);
    }

    public function scopeDiWilayah(Builder $query, int $wilayahId): Builder
    {
        return $query->where('wilayah_id', $wilayahId);
    }

    // ----------------------------------------------------------------
    // Pembantu role
    // ----------------------------------------------------------------

    /**
     * Role utama pengguna sebagai enum.
     *
     * Seorang pengguna Resikita pada praktiknya memegang satu role;
     * yang pertama diambil kalau ada lebih dari satu.
     */
    public function roleUtama(): ?RoleEnum
    {
        $nama = $this->getRoleNames()->first();

        return $nama !== null ? RoleEnum::tryFrom($nama) : null;
    }

    public function isPemerintahan(): bool
    {
        return $this->roleUtama()?->isPemerintahan() ?? false;
    }

    public function isPlatform(): bool
    {
        return $this->roleUtama()?->isPlatform() ?? false;
    }

    /** Tingkat wilayah yang dipegang pengguna; null kalau bukan role pemerintahan. */
    public function tingkatKewenangan(): ?TingkatWilayah
    {
        return $this->roleUtama()?->tingkatWilayah();
    }

    /**
     * Terbitkan kode QR nasabah kalau belum ada.
     *
     * ULID acak, sengaja tidak diturunkan dari data pribadi apa pun,
     * sehingga kode yang terlihat orang lain tidak membocorkan identitas
     * pemiliknya.
     */
    public function pastikanKodeQr(): string
    {
        if ($this->kode_qr === null) {
            $this->forceFill(['kode_qr' => (string) Str::ulid()])->save();
        }

        return $this->kode_qr;
    }

    /** URL foto profil, atau null kalau belum pernah diunggah. */
    public function urlAvatar(): ?string
    {
        return $this->avatar_path !== null ? Storage::url($this->avatar_path) : null;
    }

    /** Inisial untuk avatar cadangan, mis. "Ni Made Sari" → "NS". */
    public function inisial(): string
    {
        $kata = preg_split('/\s+/', trim($this->name)) ?: [];

        return mb_strtoupper(mb_substr($kata[0] ?? '?', 0, 1).(count($kata) > 1 ? mb_substr(end($kata), 0, 1) : ''));
    }
}
