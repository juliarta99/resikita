<?php

namespace App\Livewire\Public;

use App\Models\Tps;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public')]
class TpsShow extends Component
{
    public Tps $tps;

    public function mount(Tps $tps)
    {
        $this->tps = $tps->load('banjarDinas');
    }

    public function render()
    {
        return view('livewire.public.tps-show');
    }
}