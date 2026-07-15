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
 * Logika inti pembuatan & pembaruan akun untuk role non-mandiri.
 */
class AccountProvisioningService
{
    /**
     * Buat user + assign role + set scope.
     *
     * @param array $data  name, email, phone?, nip?, jenis_kelamin?, password?
     */
    public function provision(string $role, array $data, array $scope = [], bool $active = true): User
    {
        return DB::transaction(function () use ($role, $data, $scope, $active) {
            $user = User::create(array_merge([
                'name'          => $data['name'],
                'email'         => $data['email'] ?? null,
                'phone'         => $data['phone'] ?? null,
                'nip'           => $data['nip'] ?? null,
                'jenis_kelamin' => $data['jenis_kelamin'] ?? null,
                'password'      => Hash::make($data['password'] ?? Str::password(12)),
                'is_active'     => $active,
            ], $scope));

            $user->assignRole($role);

            return $user;
        });
    }

    /**
     * Perbarui data akun yang sudah ada (password opsional).
     */
    public function updateAccount(User $user, array $data): void
    {
        $attrs = [
            'name'          => $data['name'],
            'email'         => $data['email'],
            'nip'           => $data['nip'] ?? null,
            'jenis_kelamin' => $data['jenis_kelamin'] ?? null,
        ];

        if (! empty($data['password'])) {
            $attrs['password'] = Hash::make($data['password']);
        }

        $user->update($attrs);
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

    public function selfRegisterUmkm(array $data): Umkm
    {
        return DB::transaction(function () use ($data) {
            $umkm = Umkm::create([
                'nama'      => $data['nama'],
                'deskripsi' => $data['deskripsi'] ?? null,
                'alamat'    => $data['alamat'] ?? null,
                'no_hp'     => $data['no_hp'] ?? null,
                'status'    => 'menunggu',
            ]);

            $this->provision('umkm', [
                'name'     => $data['pemilik_name'],
                'email'    => $data['pemilik_email'],
                'phone'    => $data['pemilik_phone'] ?? null,
                'password' => $data['password'],
            ], ['umkm_id' => $umkm->id], active: false);

            return $umkm;
        });
    }

    public function approveUmkm(Umkm $umkm): void
    {
        DB::transaction(function () use ($umkm) {
            $umkm->update(['status' => 'aktif']);
            User::where('umkm_id', $umkm->id)->update(['is_active' => true]);
        });
    }

    public function rejectUmkm(Umkm $umkm): void
    {
        $umkm->update(['status' => 'ditolak']);
    }
}