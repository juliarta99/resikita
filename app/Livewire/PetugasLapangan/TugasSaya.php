<?php

namespace App\Livewire\PetugasLapangan;

use App\Exports\TableExport;
use App\Models\ReportAssignment;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.petugas')]
class TugasSaya extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $filterStatus = '';

    #[Url]
    public string $dariTanggal = '';

    #[Url]
    public string $sampaiTanggal = '';

    public function updating($field): void
    {
        // Reset halaman ketika filter berubah
        if (in_array($field, ['search', 'filterStatus', 'dariTanggal', 'sampaiTanggal'])) {
            $this->resetPage();
        }
    }

    public function resetFilter(): void
    {
        $this->reset(['search', 'filterStatus', 'dariTanggal', 'sampaiTanggal']);
        $this->resetPage();
    }

    /** Query dasar penugasan milik petugas ini + filter aktif. */
    private function baseQuery()
    {
        return ReportAssignment::query()
            ->with(['report.kategori', 'report.progress'])
            ->where('petugas_id', Auth::id())
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->dariTanggal, fn ($q) => $q->whereDate('assigned_at', '>=', $this->dariTanggal))
            ->when($this->sampaiTanggal, fn ($q) => $q->whereDate('assigned_at', '<=', $this->sampaiTanggal))
            ->when($this->search, function ($q) {
                $term = '%' . $this->search . '%';
                $q->whereHas('report', function ($r) use ($term) {
                    $r->where('judul', 'like', $term)
                        ->orWhere('alamat', 'like', $term)
                        ->orWhere('tiket_no', 'like', $term);
                });
            })
            ->latest('assigned_at')
            ->latest('id');
    }

    /** Export Excel (.xls) mengikuti filter yang sedang aktif. */
    public function exportExcel(): StreamedResponse
    {
        $data = $this->baseQuery()->get();

        $headings = ['No Tiket', 'Judul', 'Kategori', 'Status Tugas', 'Status Laporan', 'Alamat', 'Ditugaskan', 'Progress Terakhir'];

        $rows = $data->map(function ($a) {
            $r = $a->report;
            $lastProg = $r?->progress?->sortByDesc('created_at')->first();

            return [
                $r?->tiket_no ?? '-',
                $r?->judul ?? '-',
                $r?->kategori?->nama ?? '-',
                $a->status,
                $r?->status ?? '-',
                $r?->alamat ?? '-',
                optional($a->assigned_at)->format('Y-m-d H:i') ?? '-',
                $lastProg?->catatan ?? '-',
            ];
        })->all();

        $filename = 'tugas-saya-' . now()->format('Ymd-His');

        return (new TableExport($headings, $rows, 'Tugas Saya'))->download($filename);
    }

    public function render()
    {
        return view('livewire.petugas-lapangan.tugas-saya', [
            'tugas' => $this->baseQuery()->paginate(12),
        ]);
    }
}