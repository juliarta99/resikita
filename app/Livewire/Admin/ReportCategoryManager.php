<?php

namespace App\Livewire\Admin;

use App\Models\ReportCategory;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class ReportCategoryManager extends Component
{
    public bool $showForm = false;
    public bool $showDelete = false;
    public ?int $deleteId = null;

    public ?int $editingId = null;
    public string $nama = '';
    public string $deskripsi = '';

    public function tambah()
    {
        $this->batal();
        $this->showForm = true;
    }

    public function simpan()
    {
        $data = $this->validate([
            'nama'      => 'required|string|max:255',
            'deskripsi' => 'nullable|string|max:500',
        ]);

        if ($this->editingId) {
            ReportCategory::findOrFail($this->editingId)->update($data);
            $pesan = 'Kategori diperbarui.';
        } else {
            ReportCategory::create($data);
            $pesan = 'Kategori ditambahkan.';
        }

        $this->batal();
        session()->flash('ok', $pesan);
    }

    public function edit(int $id)
    {
        $c = ReportCategory::findOrFail($id);
        $this->editingId = $c->id;
        $this->nama = $c->nama;
        $this->deskripsi = $c->deskripsi ?? '';
        $this->showForm = true;
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
            ReportCategory::findOrFail($this->deleteId)->delete();
            $this->deleteId = null;
            session()->flash('ok', 'Kategori dihapus.');
        }
    }

    public function batal()
    {
        $this->reset('showForm', 'editingId', 'nama', 'deskripsi');
    }

    public function render()
    {
        return view('livewire.admin.report-category-manager', [
            'daftar' => ReportCategory::orderBy('nama')->get(),
        ]);
    }
}