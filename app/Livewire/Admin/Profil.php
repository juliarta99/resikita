<?php

namespace App\Livewire\Admin;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Profil extends Component
{
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $password = '';

    public function mount()
    {
        /** @var \App\Models\User $u */
        $u = Auth::user();
        $this->name = $u->name;
        $this->email = $u->email ?? '';
        $this->phone = $u->phone ?? '';
    }

    public function simpan()
    {
        /** @var \App\Models\User $u */
        $u = Auth::user();

        $data = $this->validate([
            'name'     => 'required|string|max:255',
            'email'    => ['required', 'email', Rule::unique('users', 'email')->ignore($u->id)],
            'phone'    => 'nullable|string|max:30',
            'password' => 'nullable|string|min:8',
        ]);

        $attrs = ['name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'] ?: null];
        if (! empty($data['password'])) {
            $attrs['password'] = Hash::make($data['password']);
        }

        $u->update($attrs);
        $this->password = '';
        session()->flash('ok', 'Profil diperbarui.');
    }

    public function render()
    {
        return view('livewire.admin.profil');
    }
}