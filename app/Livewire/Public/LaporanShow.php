<?php

namespace App\Livewire\Public;

use App\Models\Report;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public')]
class LaporanShow extends Component
{
    public Report $report;

    public function mount(Report $report)
    {
        // Laporan menunggu verifikasi / ditolak tidak diekspos ke publik
        abort_if(in_array($report->status, ['menunggu', 'ditolak'], true), 404);
        $this->report = $report->load('kategori', 'banjarDinas', 'images');
    }

    public function render()
    {
        return view('livewire.public.laporan-show', [
            'progress' => $this->report->progress()->latest()->get(),
        ]);
    }
}