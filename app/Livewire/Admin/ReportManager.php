<?php

namespace App\Livewire\Admin;

use App\Exports\TableExport;
use App\Models\Report;
use App\Models\ReportCategory;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class ReportManager extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'semua';
    public string $kategoriFilter = '';

    public bool $showDetail = false;
    public ?int $detailId = null;

    public bool $showDelete = false;
    public ?int $deleteId = null;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingKategoriFilter()
    {
        $this->resetPage();
    }

    private function baseQuery()
    {
        $q = Report::with('kategori', 'banjarDinas', 'pelapor')->latest();

        if ($this->search !== '') {
            $s = $this->search;
            $q->where(fn ($w) => $w->where('judul', 'like', "%{$s}%")->orWhere('tiket_no', 'like', "%{$s}%")->orWhere('alamat', 'like', "%{$s}%"));
        }
        if ($this->statusFilter !== 'semua') {
            $q->where('status', $this->statusFilter);
        }
        if ($this->kategoriFilter !== '') {
            $q->where('kategori_id', $this->kategoriFilter);
        }

        return $q;
    }

    public function lihat(int $id)
    {
        $this->detailId = $id;
        $this->showDetail = true;
        $r = Report::find($id);
        $this->dispatch('detail-opened', lat: $r?->lat ? (float) $r->lat : null, lng: $r?->lng ? (float) $r->lng : null);
    }

    public function konfirmHapus(int $id)
    {
        $this->deleteId = $id;
        $this->showDelete = true;
    }

    public function hapus()
    {
        $this->showDelete = false;
        $r = Report::with('images')->find($this->deleteId);
        $this->deleteId = null;
        if ($r) {
            // Hapus file gambar bukti + foto utama
            foreach ($r->images as $img) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($img->path);
            }
            if ($r->foto) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($r->foto);
            }
            $r->images()->delete();
            $r->progress()->delete();
            $r->assignments()->delete();
            $r->delete();
            session()->flash('ok', 'Laporan dihapus.');
        }
    }

    public function export()
    {
        $rows = $this->baseQuery()->get()->map(fn ($r) => [
            $r->tiket_no,
            $r->judul,
            $r->kategori?->nama,
            ucfirst($r->status),
            $r->banjarDinas?->nama,
            $r->alamat,
            $r->created_at->format('Y-m-d H:i'),
        ])->all();

        return (new TableExport(['Tiket', 'Judul', 'Kategori', 'Status', 'Banjar', 'Alamat', 'Tanggal'], $rows, 'Laporan'))->download('laporan-' . now()->format('Ymd-His') . '.xls');
    }

    public function render()
    {
        $selected = $this->detailId
            ? Report::with('kategori', 'banjarDinas', 'pelapor', 'progress', 'assignments.petugas', 'images')->find($this->detailId)
            : null;

        return view('livewire.admin.report-manager', [
            'daftar'    => $this->baseQuery()->paginate(15),
            'kategoris' => ReportCategory::orderBy('nama')->get(),
            'selected'  => $selected,
        ]);
    }
}