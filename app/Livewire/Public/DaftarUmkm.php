<?php

namespace App\Livewire\Public;

use App\Services\Domain\AccountProvisioningService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.auth', ['title' => 'Daftar UMKM'])]
#[Title('Daftar UMKM')]
class DaftarUmkm extends Component
{
    public string $nama = '';
    public string $deskripsi = '';
    public string $alamat = '';
    public string $no_hp = '';

    public string $pemilik_name = '';
    public string $pemilik_email = '';
    public string $pemilik_phone = '';
    public string $password = '';
    public string $password_confirmation = '';

    public bool $submitted = false;

    public function daftar(AccountProvisioningService $provisioning)
    {
        $data = $this->validate([
            'nama'          => 'required|string|max:255',
            'deskripsi'     => 'nullable|string|max:1000',
            'alamat'        => 'nullable|string|max:255',
            'no_hp'         => 'nullable|string|max:30',
            'pemilik_name'  => 'required|string|max:255',
            'pemilik_email' => 'required|email|unique:users,email',
            'pemilik_phone' => 'nullable|string|max:30',
            'password'      => 'required|string|min:8|confirmed',
        ]);

        $provisioning->selfRegisterUmkm($data);

        $this->submitted = true;
    }

    public function render()
    {
        return view('livewire.public.daftar-umkm');
    }
}