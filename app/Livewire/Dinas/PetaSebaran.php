<?php

namespace App\Livewire\Dinas;

use App\Models\Report;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.dinas')]
class PetaSebaran extends Component
{
    public string $dari = '';
    public string $sampai = '';
    public string $statusFilter = 'semua';
    public array $markers = [];

    public function mount()
    {
        $this->dari = now()->startOfMonth()->toDateString();
        $this->sampai = now()->toDateString();
        $this->markers = $this->computeMarkers();
    }

    private function range(): array
    {
        return [Carbon::parse($this->dari)->startOfDay(), Carbon::parse($this->sampai)->endOfDay()];
    }

    private function computeMarkers(): array
    {
        [$s, $e] = $this->range();
        $q = Report::whereBetween('created_at', [$s, $e])->whereNotNull('lat')->whereNotNull('lng');

        if ($this->statusFilter !== 'semua') {
            if ($this->statusFilter === 'proses') {
                $q->whereIn('status', ['diverifikasi', 'ditugaskan', 'proses']);
            } else {
                $q->where('status', $this->statusFilter);
            }
        }

        return $q->get()->map(fn ($r) => [
            's'   => $r->status,
            'n'   => $r->judul,
            'lat' => (float) $r->lat,
            'lng' => (float) $r->lng,
        ])->values()->all();
    }

    public function terapkan()
    {
        $this->markers = $this->computeMarkers();
        $this->dispatch('peta-updated', markers: $this->markers);
    }

    public function render()
    {
        [$s, $e] = $this->range();
        $base = Report::whereBetween('created_at', [$s, $e]);

        return view('livewire.dinas.peta-sebaran', [
            'counts' => [
                'total'    => (clone $base)->count(),
                'menunggu' => (clone $base)->where('status', 'menunggu')->count(),
                'proses'   => (clone $base)->whereIn('status', ['diverifikasi', 'ditugaskan', 'proses'])->count(),
                'selesai'  => (clone $base)->where('status', 'selesai')->count(),
                'ditolak'  => (clone $base)->where('status', 'ditolak')->count(),
            ],
        ]);
    }
}