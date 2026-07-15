<?php

namespace App\Livewire\Umkm;

use App\Models\Umkm;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.umkm')]
class Profil extends Component
{
    use WithFileUploads;

    // Info usaha
    public string $nama = '';
    public string $deskripsi = '';
    public string $alamat = '';
    public string $no_hp = '';
    public string $lat = '';
    public string $lng = '';
    public $foto;
    public ?string $fotoLama = null;

    // Akun
    public string $ownerName = '';
    public string $email = '';
    public string $password = '';

    public function mount()
    {
        $u = Auth::user();
        $umkm = Umkm::findOrFail($u->umkm_id);

        $this->nama = $umkm->nama;
        $this->deskripsi = $umkm->deskripsi ?? '';
        $this->alamat = $umkm->alamat ?? '';
        $this->no_hp = $umkm->no_hp ?? '';
        $this->lat = $umkm->lat ? (string) $umkm->lat : '';
        $this->lng = $umkm->lng ? (string) $umkm->lng : '';
        $this->fotoLama = $umkm->foto;

        $this->ownerName = $u->name;
        $this->email = $u->email ?? '';

        $this->dispatch('form-opened', lat: $this->lat ?: null, lng: $this->lng ?: null);
    }

    public function simpanUsaha()
    {
        $data = $this->validate([
            'nama'      => 'required|string|max:255',
            'deskripsi' => 'nullable|string|max:2000',
            'alamat'    => 'nullable|string|max:255',
            'no_hp'     => 'nullable|string|max:30',
            'lat'       => 'nullable|numeric|between:-90,90',
            'lng'       => 'nullable|numeric|between:-180,180',
            'foto'      => 'nullable|image|max:2048',
        ]);

        $umkm = Umkm::findOrFail(Auth::user()->umkm_id);
        $attrs = [
            'nama'      => $data['nama'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'alamat'    => $data['alamat'] ?? null,
            'no_hp'     => $data['no_hp'] ?? null,
            'lat'       => $data['lat'] ?: null,
            'lng'       => $data['lng'] ?: null,
        ];

        if ($this->foto) {
            if ($umkm->foto) {
                Storage::disk('public')->delete($umkm->foto);
            }
            $attrs['foto'] = $this->foto->store('umkm', 'public');
        }

        $umkm->update($attrs);
        $this->foto = null;
        $this->fotoLama = $umkm->foto;
        session()->flash('okUsaha', 'Profil usaha diperbarui.');
    }

    public function simpanAkun()
    {
        /** @var \App\Models\User $u */
        $u = Auth::user();
        $data = $this->validate([
            'ownerName' => 'required|string|max:255',
            'email'     => ['required', 'email', Rule::unique('users', 'email')->ignore($u->id)],
            'password'  => 'nullable|string|min:8',
        ]);

        $attrs = ['name' => $data['ownerName'], 'email' => $data['email']];
        if (! empty($data['password'])) {
            $attrs['password'] = Hash::make($data['password']);
        }

        $u->update($attrs);
        $this->password = '';
        session()->flash('okAkun', 'Akun diperbarui.');
    }

    public function render()
    {
        return view('livewire.umkm.profil');
    }
}