<?php

namespace App\Livewire\Admin;

use App\Models\WastePrice;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class HargaSampahManager extends Component
{
    public bool $showForm = false;
    public bool $showDelete = false;
    public ?int $deleteId = null;

    public ?int $editingId = null;
    public string $jenis_sampah = '';
    public string $satuan = 'kg';
    public string $harga_per_kg = '';
    public bool $is_active = true;

    public function tambah()
    {
        $this->batal();
        $this->showForm = true;
    }

    public function simpan()
    {
        $data = $this->validate([
            'jenis_sampah' => 'required|string|max:255',
            'satuan'       => 'required|string|max:20',
            'harga_per_kg' => 'required|numeric|min:0',
            'is_active'    => 'boolean',
        ]);

        if ($this->editingId) {
            WastePrice::findOrFail($this->editingId)->update($data);
            $pesan = 'Harga sampah diperbarui.';
        } else {
            WastePrice::create($data);
            $pesan = 'Harga sampah ditambahkan.';
        }

        $this->batal();
        session()->flash('ok', $pesan);
    }

    public function edit(int $id)
    {
        $w = WastePrice::findOrFail($id);
        $this->editingId = $w->id;
        $this->jenis_sampah = $w->jenis_sampah;
        $this->satuan = $w->satuan;
        $this->harga_per_kg = (string) $w->harga_per_kg;
        $this->is_active = (bool) $w->is_active;
        $this->showForm = true;
    }

    public function toggleAktif(int $id)
    {
        $w = WastePrice::findOrFail($id);
        $w->update(['is_active' => ! $w->is_active]);
    }

    public function konfirmHapus(int $id)
    {
        $this->deleteId = $id;
        $this->showDelete = true;
    }

    public function hapus()
    {
        $this->showDelete = false;
        if ($this->deleteId) {
            WastePrice::findOrFail($this->deleteId)->delete();
            $this->deleteId = null;
            session()->flash('ok', 'Harga sampah dihapus.');
        }
    }

    public function batal()
    {
        $this->reset('showForm', 'editingId', 'jenis_sampah', 'satuan', 'harga_per_kg', 'is_active');
    }

    public function render()
    {
        return view('livewire.admin.harga-sampah-manager', [
            'daftar' => WastePrice::orderBy('jenis_sampah')->get(),
        ]);
    }
}