<?php

namespace App\Livewire\Dinas;

use App\Models\ActivityLog;
use App\Models\Report;
use App\Models\ReportAssignment;
use App\Models\ReportCategory;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.dinas')]
class LaporanManager extends Component
{
    use WithPagination;

    public string $statusFilter = 'menunggu';
    public string $kategoriFilter = '';
    public string $search = '';

    public bool $showDetail = false;
    public ?int $detailId = null;

    public bool $showAssign = false;
    public ?int $assignId = null;
    public string $petugasId = '';

    public bool $showReject = false;
    public ?int $rejectId = null;
    public string $rejectCatatan = '';

    public array $statusLabels = [
        'menunggu'     => 'Menunggu',
        'diverifikasi' => 'Diverifikasi',
        'ditugaskan'   => 'Ditugaskan',
        'proses'       => 'Proses',
        'selesai'      => 'Selesai',
        'ditolak'      => 'Ditolak',
    ];

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

    public function lihat(int $id)
    {
        $r = Report::find($id);
        if (! $r) {
            return;
        }
        $this->detailId = $id;
        $this->showDetail = true;
        $this->dispatch('detail-opened', lat: (float) $r->lat, lng: (float) $r->lng);
    }

    public function verifikasi(int $id)
    {
        $r = Report::find($id);
        if ($r && $r->status === 'menunggu') {
            $r->update(['status' => 'diverifikasi', 'verified_by' => Auth::id()]);
            session()->flash('ok', 'Laporan diverifikasi.');
        }
    }

    public function konfirmTolak(int $id)
    {
        $this->rejectId = $id;
        $this->rejectCatatan = '';
        $this->showReject = true;
    }

    public function tolak()
    {
        $r = Report::find($this->rejectId);
        if ($r && in_array($r->status, ['menunggu', 'diverifikasi'])) {
            $r->update(['status' => 'ditolak', 'verified_by' => Auth::id()]);

            ActivityLog::create([
                'user_id'      => Auth::id(),
                'aksi'         => 'tolak_laporan',
                'subject_type' => $r->getMorphClass(),
                'subject_id'   => $r->id,
                'deskripsi'    => 'Laporan ditolak' . ($this->rejectCatatan ? ': ' . $this->rejectCatatan : '.'),
            ]);

            session()->flash('ok', 'Laporan ditolak.');
        }

        $this->reset('showReject', 'rejectId', 'rejectCatatan');
    }

    public function bukaTugas(int $id)
    {
        $this->assignId = $id;
        $this->petugasId = '';
        $this->showAssign = true;
    }

    public function tugaskan()
    {
        $this->validate(['petugasId' => 'required|exists:users,id']);

        $r = Report::find($this->assignId);
        if ($r && in_array($r->status, ['diverifikasi', 'ditugaskan'])) {
            ReportAssignment::create([
                'report_id'   => $r->id,
                'petugas_id'  => $this->petugasId,
                'assigned_by' => Auth::id(),
                'status'      => 'ditugaskan',
                'assigned_at' => now(),
            ]);
            $r->update(['status' => 'ditugaskan']);
            session()->flash('ok', 'Laporan ditugaskan ke petugas.');
        }

        $this->reset('showAssign', 'assignId', 'petugasId');
    }

    public function tandaiSelesai(int $id)
    {
        $r = Report::find($id);
        if ($r && in_array($r->status, ['ditugaskan', 'proses'])) {
            $r->update(['status' => 'selesai']);
            ReportAssignment::where('report_id', $r->id)->latest()->first()?->update(['status' => 'selesai']);
            session()->flash('ok', 'Laporan ditandai selesai.');
        }
    }

    public function render()
    {
        $query = Report::with(['pelapor', 'kategori', 'banjarDinas'])->latest();

        if ($this->statusFilter !== 'semua') {
            $query->where('status', $this->statusFilter);
        }
        if ($this->kategoriFilter !== '') {
            $query->where('kategori_id', $this->kategoriFilter);
        }
        if ($this->search !== '') {
            $s = $this->search;
            $query->where(fn ($w) => $w->where('judul', 'like', "%{$s}%")->orWhere('tiket_no', 'like', "%{$s}%"));
        }

        $detail = null;
        if ($this->showDetail && $this->detailId) {
            $detail = Report::with(['pelapor', 'kategori', 'banjarDinas.kelurahan', 'assignments.petugas', 'progress.petugas'])
                ->find($this->detailId);
        }

        return view('livewire.dinas.laporan-manager', [
            'laporan'      => $query->paginate(12),
            'kategoriList' => ReportCategory::orderBy('nama')->get(),
            'petugasList'  => User::role('petugas_lapangan')->orderBy('name')->get(),
            'detail'       => $detail,
        ]);
    }
}