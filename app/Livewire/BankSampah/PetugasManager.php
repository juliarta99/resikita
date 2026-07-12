<?php

namespace App\Livewire\BankSampah;

use App\Models\BankSampah;
use App\Models\User;
use App\Services\Domain\AccountProvisioningService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.banksampah')]
class PetugasManager extends Component
{
    public bool $showForm = false;
    public string $name = '';
    public string $email = '';
    public string $password = '';

    public bool $showReset = false;
    public ?int $resetId = null;
    public string $new_password = '';

    public bool $showDelete = false;
    public ?int $deleteId = null;

    private function bsId(): int
    {
        return Auth::user()->bank_sampah_id;
    }

    public function tambah()
    {
        $this->reset('name', 'email', 'password');
        $this->showForm = true;
    }

    public function simpan(AccountProvisioningService $provisioning)
    {
        $data = $this->validate([
            'name'     => 'required|string|max:255',
            'email'    => ['required', 'email', Rule::unique('users', 'email')],
            'password' => 'required|string|min:8',
        ]);

        $bs = BankSampah::findOrFail($this->bsId());
        $provisioning->createPetugasBankSampah($bs, $data);

        $this->reset('showForm', 'name', 'email', 'password');
        session()->flash('ok', 'Petugas berhasil ditambahkan.');
    }

    public function toggleAktif(int $id)
    {
        $u = User::role('petugas_bank_sampah')->where('bank_sampah_id', $this->bsId())->find($id);
        if ($u) {
            $u->update(['is_active' => ! $u->is_active]);
        }
    }

    public function bukaReset(int $id)
    {
        $this->resetId = $id;
        $this->new_password = '';
        $this->showReset = true;
    }

    public function simpanReset()
    {
        $this->validate(['new_password' => 'required|string|min:8']);
        $u = User::role('petugas_bank_sampah')->where('bank_sampah_id', $this->bsId())->findOrFail($this->resetId);
        $u->update(['password' => Hash::make($this->new_password)]);
        $this->reset('showReset', 'resetId', 'new_password');
        session()->flash('ok', 'Kata sandi petugas direset.');
    }

    public function konfirmHapus(int $id)
    {
        $this->deleteId = $id;
        $this->showDelete = true;
    }

    public function hapus()
    {
        $this->showDelete = false;
        $u = User::role('petugas_bank_sampah')->where('bank_sampah_id', $this->bsId())->find($this->deleteId);
        $this->deleteId = null;
        if ($u) {
            $u->delete();
            session()->flash('ok', 'Petugas dihapus.');
        }
    }

    public function render()
    {
        return view('livewire.bank-sampah.petugas-manager', [
            'petugas' => User::role('petugas_bank_sampah')->where('bank_sampah_id', $this->bsId())->orderBy('name')->get(),
        ]);
    }
}