<?php

namespace App\Livewire\Public;

use App\Models\Umkm;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.public')]
class UmkmIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $q = Umkm::where('status', 'aktif')->withCount('products')->latest();
        if ($this->search !== '') {
            $q->where('nama', 'like', '%' . $this->search . '%');
        }

        return view('livewire.public.umkm-index', ['umkms' => $q->paginate(9)]);
    }
}