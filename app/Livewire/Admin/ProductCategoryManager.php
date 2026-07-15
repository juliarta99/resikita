<?php

namespace App\Livewire\Admin;

use App\Models\ProductCategory;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class ProductCategoryManager extends Component
{
    public bool $showForm = false;
    public bool $showDelete = false;
    public ?int $deleteId = null;

    public ?int $editingId = null;
    public string $nama = '';

    public function tambah()
    {
        $this->batal();
        $this->showForm = true;
    }

    public function simpan()
    {
        $data = $this->validate([
            'nama' => 'required|string|max:255',
        ]);

        if ($this->editingId) {
            ProductCategory::findOrFail($this->editingId)->update($data);
            $pesan = 'Kategori diperbarui.';
        } else {
            ProductCategory::create($data);
            $pesan = 'Kategori ditambahkan.';
        }

        $this->batal();
        session()->flash('ok', $pesan);
    }

    public function edit(int $id)
    {
        $c = ProductCategory::findOrFail($id);
        $this->editingId = $c->id;
        $this->nama = $c->nama;
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
            ProductCategory::findOrFail($this->deleteId)->delete();
            $this->deleteId = null;
            session()->flash('ok', 'Kategori dihapus.');
        }
    }

    public function batal()
    {
        $this->reset('showForm', 'editingId', 'nama');
    }

    public function render()
    {
        return view('livewire.admin.product-category-manager', [
            'daftar' => ProductCategory::orderBy('nama')->get(),
        ]);
    }
}