<?php

namespace App\Livewire\PetugasLapangan;

use App\Models\ReportAssignment;
use App\Models\ReportCategory;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.petugas')]
class PetaSebaran extends Component
{
    #[Url]
    public string $filterStatus = '';

    #[Url]
    public string $filterKategori = '';

    #[Url]
    public string $dariTanggal = '';

    #[Url]
    public string $sampaiTanggal = '';

    /** Properti publik agar bisa di-entangle ke Alpine dan reaktif. */
    public array $markers = [];

    public function mount(): void
    {
        $this->refreshMarkers();
    }

    /** Recompute setiap kali salah satu filter berubah. */
    public function updated($property): void
    {
        if (in_array($property, ['filterStatus', 'filterKategori', 'dariTanggal', 'sampaiTanggal'])) {
            $this->refreshMarkers();
        }
    }

    public function resetFilter(): void
    {
        $this->reset(['filterStatus', 'filterKategori', 'dariTanggal', 'sampaiTanggal']);
        $this->refreshMarkers();
    }

    /** Marker laporan yang di-assign ke petugas ini (dengan koordinat valid). */
    public function refreshMarkers(): void
    {
        $this->markers = ReportAssignment::query()
            ->with(['report.kategori'])
            ->where('petugas_id', Auth::id())
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->dariTanggal, fn ($q) => $q->whereDate('assigned_at', '>=', $this->dariTanggal))
            ->when($this->sampaiTanggal, fn ($q) => $q->whereDate('assigned_at', '<=', $this->sampaiTanggal))
            ->when($this->filterKategori, fn ($q) => $q->whereHas('report', fn ($r) => $r->where('kategori_id', $this->filterKategori)))
            ->get()
            ->filter(fn ($a) => $a->report && $a->report->lat && $a->report->lng)
            ->map(fn ($a) => [
                'id'       => $a->report->id,
                'lat'      => (float) $a->report->lat,
                'lng'      => (float) $a->report->lng,
                'judul'    => $a->report->judul,
                'kategori' => $a->report->kategori?->nama ?? '-',
                'status'   => $a->status,
                'alamat'   => $a->report->alamat ?? '-',
                'url'      => route('petugas.tugas.detail', $a->report->id),
            ])
            ->values()
            ->all();
    }

    public function render()
    {
        return view('livewire.petugas-lapangan.peta-sebaran', [
            'kategori' => ReportCategory::orderBy('nama')->get(),
        ]);
    }
}