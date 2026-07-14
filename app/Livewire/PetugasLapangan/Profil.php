<?php

namespace App\Livewire\PetugasLapangan;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.petugas')]
class Profil extends Component
{
    // Data profil
    public string $name = '';
    public string $phone = '';
    public string $email = '';

    // Ubah password
    public string $password_lama = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(): void
    {
        $u = Auth::user();
        $this->name = $u->name ?? '';
        $this->phone = $u->phone ?? '';
        $this->email = $u->email ?? '';
    }

    public function simpanProfil(): void
    {
        /** @var \App\Models\User $u */
        $u = Auth::user();

        $data = $this->validate([
            'name'  => 'required|string|max:100',
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($u->id)],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('users', 'email')->ignore($u->id)],
        ], [], [
            'name'  => 'nama',
            'phone' => 'nomor telepon',
            'email' => 'email',
        ]);

        $u->update([
            'name'  => $data['name'],
            'phone' => $data['phone'] ?: null,
            'email' => $data['email'] ?: null,
        ]);

        session()->flash('ok_profil', 'Profil berhasil diperbarui.');
    }

    public function ubahPassword(): void
    {
        $this->validate([
            'password_lama'         => 'required',
            'password'             => 'required|string|min:8|confirmed',
        ], [], [
            'password_lama' => 'password lama',
            'password'      => 'password baru',
        ]);

        /** @var \App\Models\User $u */
        $u = Auth::user();

        if (! Hash::check($this->password_lama, $u->password)) {
            $this->addError('password_lama', 'Password lama tidak sesuai.');
            return;
        }

        $u->update(['password' => Hash::make($this->password)]);

        $this->reset(['password_lama', 'password', 'password_confirmation']);
        session()->flash('ok_password', 'Password berhasil diubah.');
    }

    public function render()
    {
        return view('livewire.petugas-lapangan.profil', [
            'user' => Auth::user(),
        ]);
    }
}
