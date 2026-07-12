<?php

namespace App\Livewire\Public;

use App\Models\Umkm;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public')]
class UmkmShow extends Component
{
    public Umkm $umkm;

    public function mount(Umkm $umkm)
    {
        abort_if($umkm->status !== 'aktif', 404);
        $this->umkm = $umkm->load('banjarDinas');
    }

    public function render()
    {
        return view('livewire.public.umkm-show', [
            'produk' => $this->umkm->products()->where('is_active', true)->with('images')->get(),
        ]);
    }
}