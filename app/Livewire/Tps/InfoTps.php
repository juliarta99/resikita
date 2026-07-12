<?php

namespace App\Livewire\Tps;

use App\Models\Tps;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.tps')]
class InfoTps extends Component
{
    use WithFileUploads;

    public string $nama = '';
    public string $alamat = '';
    public string $no_hp = '';
    public string $lat = '';
    public string $lng = '';
    public bool $is_berbayar = false;
    public string $tarif = '';
    public $foto;
    public ?string $fotoLama = null;

    public function mount()
    {
        $tps = Tps::findOrFail(Auth::user()->tps_id);
        $this->nama = $tps->nama;
        $this->alamat = $tps->alamat ?? '';
        $this->no_hp = $tps->no_hp ?? '';
        $this->lat = $tps->lat ? (string) $tps->lat : '';
        $this->lng = $tps->lng ? (string) $tps->lng : '';
        $this->is_berbayar = (bool) $tps->is_berbayar;
        $this->tarif = $tps->tarif ? (string) (int) $tps->tarif : '';
        $this->fotoLama = $tps->foto;

        $this->dispatch('form-opened', lat: $this->lat ?: null, lng: $this->lng ?: null);
    }

    public function simpan()
    {
        $data = $this->validate([
            'nama'        => 'required|string|max:255',
            'alamat'      => 'nullable|string|max:255',
            'no_hp'       => 'nullable|string|max:30',
            'lat'         => 'nullable|numeric|between:-90,90',
            'lng'         => 'nullable|numeric|between:-180,180',
            'is_berbayar' => 'boolean',
            'tarif'       => 'nullable|numeric|min:0',
            'foto'        => 'nullable|image|max:2048',
        ]);

        $tps = Tps::findOrFail(Auth::user()->tps_id);
        $attrs = [
            'nama'        => $data['nama'],
            'alamat'      => $data['alamat'] ?? null,
            'no_hp'       => $data['no_hp'] ?? null,
            'lat'         => $data['lat'] ?: null,
            'lng'         => $data['lng'] ?: null,
            'is_berbayar' => $this->is_berbayar,
            'tarif'       => $this->is_berbayar ? (float) ($data['tarif'] ?: 0) : 0,
        ];

        if ($this->foto) {
            if ($tps->foto) {
                Storage::disk('public')->delete($tps->foto);
            }
            $attrs['foto'] = $this->foto->store('tps', 'public');
        }

        $tps->update($attrs);
        $this->foto = null;
        $this->fotoLama = $tps->foto;
        session()->flash('ok', 'Informasi TPS diperbarui.');
    }

    public function render()
    {
        return view('livewire.tps.info-tps');
    }
}