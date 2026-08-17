<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\Laporan;
use App\Models\User;
use App\Services\Wilayah\WilayahScopeService;

/**
 * Kewenangan atas sebuah laporan.
 *
 * Dua lapis yang harus terpenuhi bersamaan:
 *
 * 1. **Permission**, apakah rolenya boleh melakukan tindakan ini.
 * 2. **Cakupan wilayah**, apakah laporan ini termasuk yang boleh
 *    disentuhnya.
 *
 * Lapis kedua yang mudah terlupakan. Seorang admin kabupaten memang
 * punya `laporan.verifikasi`, tapi hanya untuk kabupatennya sendiri.
 * Memeriksa permission saja akan mengizinkannya memverifikasi laporan
 * kabupaten tetangga.
 */
class LaporanPolicy
{
    public function __construct(
        private readonly WilayahScopeService $scope,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->can(Permission::LaporanLihat->value)
            || $user->can(Permission::LaporanBuat->value);
    }

    /**
     * Laporan bersifat terbuka: siapa pun boleh melihat detail laporan
     * warga lain. Itu bagian dari transparansi yang membuat pelaporan
     * terasa berguna, pelapor bisa melihat bahwa masalah serupa
     * ditangani, dan penanganan yang mandek jadi terlihat publik.
     */
    public function view(?User $user, Laporan $laporan): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::LaporanBuat->value);
    }

    public function verifikasi(User $user, Laporan $laporan): bool
    {
        return $user->can(Permission::LaporanVerifikasi->value)
            && $this->memegang($user, $laporan);
    }

    public function tolak(User $user, Laporan $laporan): bool
    {
        return $user->can(Permission::LaporanTolak->value)
            && $this->memegang($user, $laporan);
    }

    public function tugaskan(User $user, Laporan $laporan): bool
    {
        return $user->can(Permission::LaporanTugaskan->value)
            && $this->memegang($user, $laporan);
    }

    public function gabungkan(User $user, Laporan $laporan): bool
    {
        return $user->can(Permission::LaporanGabungkan->value)
            && $this->memegang($user, $laporan);
    }

    /** Petugas hanya boleh mengerjakan laporan yang ditugaskan padanya. */
    public function kerjakan(User $user, Laporan $laporan): bool
    {
        if (! $user->can(Permission::LaporanKerjakan->value)) {
            return false;
        }

        return $laporan->penugasan()
            ->milikPetugas($user->id)
            ->aktif()
            ->exists();
    }

    /**
     * Tindak lanjut ke dinas hanya berlaku untuk laporan dari wilayah
     * yang belum terjangkau, di wilayah terverifikasi, penanganannya
     * lewat penugasan petugas, bukan lewat surat ke dinas.
     */
    public function tindakLanjut(User $user, Laporan $laporan): bool
    {
        return $user->can(Permission::LaporanTindakLanjut->value)
            && ($laporan->alasan_routing?->menaikkanSkorPrioritas() ?? false)
            && ($laporan->penanggung_jawab_id === $user->id || $user->hasRole(Role::SuperAdmin->value));
    }

    /**
     * Apakah pengguna adalah pihak yang memegang laporan ini.
     *
     * Role platform selalu boleh; role pemerintahan harus cocok dengan
     * cakupan wilayahnya.
     */
    private function memegang(User $user, Laporan $laporan): bool
    {
        if ($user->roleUtama()?->isPlatform() === true) {
            return true;
        }

        if ($laporan->penanggung_jawab_id === $user->id) {
            return true;
        }

        $wilayahLaporan = match ($user->tingkatKewenangan()?->value) {
            'provinsi' => $laporan->provinsi_id,
            'kabupaten' => $laporan->kabupaten_id,
            'desa' => $laporan->desa_id,
            default => null,
        };

        return $wilayahLaporan !== null
            && $this->scope->berwenangAtas($user, $wilayahLaporan);
    }
}
