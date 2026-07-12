<?php

namespace App\Livewire\Admin;

use App\Models\Kecamatan;
use App\Services\Domain\AccountProvisioningService;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Contoh pola provisioning: menambah kecamatan sekaligus membuat akun Camat.
 * Pola yang sama dipakai untuk Kelurahan (+Lurah), Banjar Dinas (+Kepala Dinas Banjar),
 * TPS (+Admin TPS), Bank Sampah (+Admin/Petugas), dan UMKM (+Admin UMKM).
 */
#[Layout('components.layouts.app')]
class KecamatanManager extends Component
{
    public string $nama = '';
    public string $camat_name = '';
    public string $camat_email = '';
    public string $camat_password = '';

    public function simpan(AccountProvisioningService $provisioning)
    {
        $data = $this->validate([
            'nama'           => 'required|string|max:255',
            'camat_name'     => 'required|string|max:255',
            'camat_email'    => 'required|email|unique:users,email',
            'camat_password' => 'required|string|min:8',
        ]);

        $kecamatan = Kecamatan::create(['nama' => $data['nama']]);

        $provisioning->createCamat($kecamatan, [
            'name'     => $data['camat_name'],
            'email'    => $data['camat_email'],
            'password' => $data['camat_password'],
        ]);

        $this->reset('nama', 'camat_name', 'camat_email', 'camat_password');
        session()->flash('ok', 'Kecamatan dan akun Camat berhasil dibuat.');
    }

    public function render()
    {
        return view('livewire.admin.kecamatan-manager', [
            'daftar' => Kecamatan::latest()->get(),
        ]);
    }
}
