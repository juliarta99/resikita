<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\PengajuanWilayah;
use App\Models\User;

/**
 * Peninjauan pengajuan wilayah.
 *
 * Persetujuan di sini menerbitkan akun dengan kewenangan atas seluruh
 * laporan satu daerah. Karena itu hanya super admin yang boleh
 * meninjau, bukan kekakuan berlebihan, melainkan sepadan dengan akibat
 * yang ditimbulkannya.
 */
class PengajuanWilayahPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::PengajuanWilayahLihat->value);
    }

    public function view(User $user, PengajuanWilayah $pengajuan): bool
    {
        return $user->can(Permission::PengajuanWilayahLihat->value);
    }

    /**
     * Pengajuan dibuka untuk umum: pemohon adalah pejabat daerah yang
     * belum punya akun Resikita, jadi mensyaratkan login akan membuat
     * fitur ini mustahil dipakai oleh orang yang justru dituju.
     */
    public function create(?User $user): bool
    {
        return true;
    }

    public function tinjau(User $user, PengajuanWilayah $pengajuan): bool
    {
        return $user->can(Permission::PengajuanWilayahVerifikasi->value)
            && ! $pengajuan->sudahDitinjau();
    }

    /** Melihat berkas surat tugas, yang memuat identitas pejabat. */
    public function lihatSurat(User $user, PengajuanWilayah $pengajuan): bool
    {
        return $user->can(Permission::PengajuanWilayahVerifikasi->value);
    }
}
