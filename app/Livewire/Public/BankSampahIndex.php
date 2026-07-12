<?php

namespace App\Livewire\Public;

use App\Models\BankSampah;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.public')]
class BankSampahIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $q = BankSampah::with('banjarDinas')->latest();
        if ($this->search !== '') {
            $q->where('nama', 'like', '%' . $this->search . '%')->orWhere('alamat', 'like', '%' . $this->search . '%');
        }

        return view('livewire.public.bank-sampah-index', ['daftar' => $q->paginate(9)]);
    }
}