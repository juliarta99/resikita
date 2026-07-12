<?php

namespace App\Livewire\BankSampah;

use App\Models\WastePrice;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.banksampah')]
class HargaView extends Component
{
    public function render()
    {
        return view('livewire.bank-sampah.harga-view', [
            'prices' => WastePrice::orderByDesc('is_active')->orderBy('jenis_sampah')->get(),
        ]);
    }
}