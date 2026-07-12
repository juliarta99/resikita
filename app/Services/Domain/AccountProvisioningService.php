<?php

namespace App\Services\Domain;

use App\Models\BankSampah;
use App\Models\BanjarDinas;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Tps;
use App\Models\Umkm;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Logika inti pembuatan akun untuk semua role non-mandiri.
 * Dipakai oleh panel web (Livewire) saat provisioning akun bertingkat.
 */
class AccountProvisioningService
{
    /**
     * Buat user + assign role + set scope wilayah/entitas.
     *
     * @param array $data  ['name', 'email'(opsional), 'phone'(opsional), 'password'(opsional)]
     * @param array $scope kolom scope: kecamatan_id, kelurahan_id, banjar_id, tps_id, bank_sampah_id, umkm_id
     */
    public function provision(string $role, array $data, array $scope = []): User
    {
        return DB::transaction(function () use ($role, $data, $scope) {
            $user = User::create(array_merge([
                'name'      => $data['name'],
                'email'     => $data['email'] ?? null,
                'phone'     => $data['phone'] ?? null,
                'password'  => Hash::make($data['password'] ?? Str::password(12)),
                'is_active' => true,
            ], $scope));

            $user->assignRole($role);

            return $user;
        });
    }

    public function createCamat(Kecamatan $kecamatan, array $data): User
    {
        return $this->provision('camat', $data, ['kecamatan_id' => $kecamatan->id]);
    }

    public function createLurah(Kelurahan $kelurahan, array $data): User
    {
        return $this->provision('lurah', $data, [
            'kecamatan_id' => $kelurahan->kecamatan_id,
            'kelurahan_id' => $kelurahan->id,
        ]);
    }

    public function createKepalaDinasBanjar(BanjarDinas $banjar, array $data): User
    {
        $kelurahan = $banjar->kelurahan;

        return $this->provision('kepala_dinas_banjar', $data, [
            'kecamatan_id' => $kelurahan->kecamatan_id,
            'kelurahan_id' => $kelurahan->id,
            'banjar_id'    => $banjar->id,
        ]);
    }

    public function createAdminTps(Tps $tps, array $data): User
    {
        return $this->provision('admin_tps', $data, ['tps_id' => $tps->id]);
    }

    public function createAdminBankSampah(BankSampah $bankSampah, array $data): User
    {
        return $this->provision('admin_bank_sampah', $data, ['bank_sampah_id' => $bankSampah->id]);
    }

    public function createPetugasBankSampah(BankSampah $bankSampah, array $data): User
    {
        return $this->provision('petugas_bank_sampah', $data, ['bank_sampah_id' => $bankSampah->id]);
    }

    public function createAdminUmkm(Umkm $umkm, array $data): User
    {
        return $this->provision('umkm', $data, ['umkm_id' => $umkm->id]);
    }

    public function createPetugasLapangan(array $data, array $scope = []): User
    {
        return $this->provision('petugas_lapangan', $data, $scope);
    }
}
