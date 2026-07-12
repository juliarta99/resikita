<?php

namespace App\Livewire\Public;

use App\Models\BankSampah;
use App\Models\WastePrice;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public')]
class BankSampahShow extends Component
{
    public BankSampah $bankSampah;

    public function mount(BankSampah $bankSampah)
    {
        $this->bankSampah = $bankSampah->load('banjarDinas');
    }

    public function render()
    {
        return view('livewire.public.bank-sampah-show', [
            'harga' => WastePrice::where('is_active', true)->orderBy('jenis_sampah')->get(),
        ]);
    }
}