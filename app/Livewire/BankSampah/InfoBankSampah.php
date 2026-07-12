<?php

namespace App\Livewire\BankSampah;

use App\Models\BankSampah;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.banksampah')]
class InfoBankSampah extends Component
{
    use WithFileUploads;

    public string $nama = '';
    public string $alamat = '';
    public string $no_hp = '';
    public string $lat = '';
    public string $lng = '';
    public $foto;
    public ?string $fotoLama = null;

    public function mount()
    {
        $bs = BankSampah::findOrFail(Auth::user()->bank_sampah_id);
        $this->nama = $bs->nama;
        $this->alamat = $bs->alamat ?? '';
        $this->no_hp = $bs->no_hp ?? '';
        $this->lat = $bs->lat ? (string) $bs->lat : '';
        $this->lng = $bs->lng ? (string) $bs->lng : '';
        $this->fotoLama = $bs->foto;

        $this->dispatch('form-opened', lat: $this->lat ?: null, lng: $this->lng ?: null);
    }

    public function simpan()
    {
        $data = $this->validate([
            'nama'   => 'required|string|max:255',
            'alamat' => 'nullable|string|max:255',
            'no_hp'  => 'nullable|string|max:30',
            'lat'    => 'nullable|numeric|between:-90,90',
            'lng'    => 'nullable|numeric|between:-180,180',
            'foto'   => 'nullable|image|max:2048',
        ]);

        $bs = BankSampah::findOrFail(Auth::user()->bank_sampah_id);

        $attrs = [
            'nama'   => $data['nama'],
            'alamat' => $data['alamat'] ?? null,
            'no_hp'  => $data['no_hp'] ?? null,
            'lat'    => $data['lat'] ?: null,
            'lng'    => $data['lng'] ?: null,
        ];

        if ($this->foto) {
            if ($bs->foto) {
                Storage::disk('public')->delete($bs->foto);
            }
            $attrs['foto'] = $this->foto->store('bank-sampah', 'public');
        }

        $bs->update($attrs);
        $this->foto = null;
        $this->fotoLama = $bs->foto;

        session()->flash('ok', 'Informasi bank sampah diperbarui.');
    }

    public function render()
    {
        return view('livewire.bank-sampah.info-bank-sampah');
    }
}