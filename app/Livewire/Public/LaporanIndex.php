<?php

namespace App\Livewire\Public;

use App\Models\Report;
use App\Models\ReportCategory;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.public')]
class LaporanIndex extends Component
{
    use WithPagination;

    public string $statusFilter = 'semua';
    public string $kategoriFilter = '';

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingKategoriFilter()
    {
        $this->resetPage();
    }

    public function render()
    {
        // Hanya laporan yang sudah diverifikasi (bukan menunggu/ditolak) yang tampil publik
        $q = Report::with('kategori')
            ->whereIn('status', ['diverifikasi', 'ditugaskan', 'proses', 'selesai'])
            ->latest();

        if ($this->statusFilter !== 'semua') {
            $q->where('status', $this->statusFilter);
        }
        if ($this->kategoriFilter !== '') {
            $q->where('kategori_id', $this->kategoriFilter);
        }

        return view('livewire.public.laporan-index', [
            'daftar'    => $q->paginate(12),
            'kategoris' => ReportCategory::orderBy('nama')->get(),
        ]);
    }
}