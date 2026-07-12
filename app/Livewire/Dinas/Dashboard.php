<?php

namespace App\Livewire\Dinas;

use App\Models\Report;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.dinas')]
class Dashboard extends Component
{
    private function tren(): array
    {
        $labels = [];
        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $labels[] = $m->format('M Y');
            $data[] = Report::whereYear('created_at', $m->year)->whereMonth('created_at', $m->month)->count();
        }

        return ['labels' => $labels, 'data' => $data];
    }

    public function render()
    {
        $tren = $this->tren();

        return view('livewire.dinas.dashboard', [
            'stat' => [
                'total'     => Report::count(),
                'menunggu'  => Report::where('status', 'menunggu')->count(),
                'proses'    => Report::whereIn('status', ['diverifikasi', 'ditugaskan', 'proses'])->count(),
                'selesai'   => Report::where('status', 'selesai')->count(),
                'ditolak'   => Report::where('status', 'ditolak')->count(),
                'bulanIni'  => Report::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
                'petugas'   => User::role('petugas_lapangan')->count(),
            ],
            'trenLabels' => $tren['labels'],
            'trenData'   => $tren['data'],
            'lapData'    => [
                Report::where('status', 'menunggu')->count(),
                Report::whereIn('status', ['diverifikasi', 'ditugaskan', 'proses'])->count(),
                Report::where('status', 'selesai')->count(),
                Report::where('status', 'ditolak')->count(),
            ],
            'terbaru' => Report::with('kategori')->latest()->take(6)->get(),
        ]);
    }
}