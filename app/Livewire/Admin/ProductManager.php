<?php

namespace App\Livewire\Admin;

use App\Exports\TableExport;
use App\Models\Product;
use App\Models\Umkm;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class ProductManager extends Component
{
    use WithPagination;

    public string $search = '';
    public string $umkmFilter = '';
    public string $aktifFilter = 'semua';

    public bool $showDetail = false;
    public ?int $detailId = null;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingUmkmFilter()
    {
        $this->resetPage();
    }

    public function updatingAktifFilter()
    {
        $this->resetPage();
    }

    private function baseQuery()
    {
        $q = Product::with('umkm', 'kategori')->latest();

        if ($this->search !== '') {
            $q->where('nama', 'like', '%' . $this->search . '%');
        }
        if ($this->umkmFilter !== '') {
            $q->where('umkm_id', $this->umkmFilter);
        }
        if ($this->aktifFilter === 'aktif') {
            $q->where('is_active', true);
        } elseif ($this->aktifFilter === 'nonaktif') {
            $q->where('is_active', false);
        }

        return $q;
    }

    public function lihat(int $id)
    {
        $this->detailId = $id;
        $this->showDetail = true;
    }

    public function toggleAktif(int $id)
    {
        $p = Product::find($id);
        if ($p) {
            $p->update(['is_active' => ! $p->is_active]);
            session()->flash('ok', $p->is_active ? 'Produk diaktifkan.' : 'Produk dinonaktifkan.');
        }
    }

    public function export()
    {
        $rows = $this->baseQuery()->get()->map(fn ($p) => [
            $p->nama,
            $p->umkm?->nama,
            $p->kategori?->nama,
            (float) $p->harga,
            $p->stok,
            $p->is_active ? 'Aktif' : 'Nonaktif',
        ])->all();

        return (new TableExport(['Produk', 'UMKM', 'Kategori', 'Harga', 'Stok', 'Status'], $rows, 'Produk'))->download('produk-' . now()->format('Ymd-His') . '.xls');
    }

    public function render()
    {
        $selected = $this->detailId ? Product::with('umkm', 'kategori', 'images')->find($this->detailId) : null;

        return view('livewire.admin.product-manager', [
            'daftar'   => $this->baseQuery()->paginate(15),
            'umkms'    => Umkm::orderBy('nama')->get(),
            'selected' => $selected,
        ]);
    }
}