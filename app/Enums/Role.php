<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

/**
 * Sepuluh role Resikita (CLAUDE.md 6.2).
 *
 * Enum ini adalah cermin dari nama role Spatie, bukan penggantinya.
 * Gunakan `Role::AdminKabupaten->value` di middleware dan pengecekan
 * permission supaya nama role tidak pernah ditulis sebagai string mentah
 * yang tersebar dan bisa salah ketik.
 */
enum Role: string implements HasLabel
{
    use ProvidesOptions;

    // Platform
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case FasilitatorWilayah = 'fasilitator_wilayah';

    // Pemerintahan, aktif setelah pengajuan wilayah diverifikasi
    case AdminProvinsi = 'admin_provinsi';
    case AdminKabupaten = 'admin_kabupaten';
    case KepalaDesa = 'kepala_desa';

    // Operasional
    case Petugas = 'petugas';

    // Non-pemerintah
    case Masyarakat = 'masyarakat';
    case BankSampah = 'bank_sampah';
    case Umkm = 'umkm';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Admin',
            self::FasilitatorWilayah => 'Fasilitator Wilayah',
            self::AdminProvinsi => 'Admin Provinsi',
            self::AdminKabupaten => 'Admin Kabupaten/Kota',
            self::KepalaDesa => 'Kepala Desa/Lurah',
            self::Petugas => 'Petugas',
            self::Masyarakat => 'Masyarakat',
            self::BankSampah => 'Bank Sampah',
            self::Umkm => 'UMKM',
        };
    }

    /** Kelompok role, dipakai untuk memilih layout dan menu navigasi. */
    public function kelompok(): string
    {
        return match ($this) {
            self::SuperAdmin, self::Admin, self::FasilitatorWilayah => 'platform',
            self::AdminProvinsi, self::AdminKabupaten, self::KepalaDesa => 'pemerintahan',
            self::Petugas => 'operasional',
            self::Masyarakat, self::BankSampah, self::Umkm => 'non_pemerintah',
        };
    }

    /**
     * Role pemerintahan mendapat cakupan data dari `users.wilayah_id`.
     * Setiap query untuk role ini wajib lewat WilayahScopeService.
     */
    public function isPemerintahan(): bool
    {
        return $this->kelompok() === 'pemerintahan';
    }

    /** Role platform melihat data lintas wilayah tanpa pembatasan. */
    public function isPlatform(): bool
    {
        return $this->kelompok() === 'platform';
    }

    /**
     * Tingkat wilayah yang dipegang role pemerintahan.
     * null untuk role yang cakupannya bukan wilayah administratif.
     */
    public function tingkatWilayah(): ?TingkatWilayah
    {
        return match ($this) {
            self::AdminProvinsi => TingkatWilayah::Provinsi,
            self::AdminKabupaten => TingkatWilayah::Kabupaten,
            self::KepalaDesa => TingkatWilayah::Desa,
            default => null,
        };
    }

    /**
     * Role yang wajib punya `users.wilayah_id` terisi.
     * Petugas ikut karena terikat wilayah induk yang membuatnya.
     */
    public function butuhWilayah(): bool
    {
        return $this->isPemerintahan() || $this === self::Petugas;
    }

    /** Role yang punya antarmuka web (CLAUDE.md 8, daftar folder Livewire). */
    public function punyaAksesWeb(): bool
    {
        return match ($this) {
            self::Petugas, self::Masyarakat => false,
            default => true,
        };
    }

    /** Nama route dasbor sesuai role, dipakai setelah login berhasil. */
    public function routeDasbor(): string
    {
        return match ($this) {
            self::SuperAdmin, self::Admin => 'admin.dashboard',
            self::FasilitatorWilayah => 'fasilitator.dashboard',
            self::AdminProvinsi => 'provinsi.dashboard',
            self::AdminKabupaten => 'kabupaten.dashboard',
            self::KepalaDesa => 'desa.dashboard',
            self::BankSampah => 'bank-sampah.dashboard',
            self::Umkm => 'umkm.dashboard',
            self::Petugas, self::Masyarakat => 'beranda',
        };
    }

    /** @return array<int, string> Nilai role yang boleh mengelola wilayah tertentu. */
    public static function pemerintahan(): array
    {
        return array_values(array_map(
            static fn (self $case): string => $case->value,
            array_filter(self::cases(), static fn (self $case): bool => $case->isPemerintahan()),
        ));
    }
}
