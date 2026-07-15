<?php

namespace App\Livewire\PetugasLapangan;

use App\Models\Report;
use App\Models\ReportAssignment;
use App\Models\ReportProgress;
use App\Services\Integration\FonnteService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.petugas')]
class TugasDetail extends Component
{
    use WithFileUploads;

    public Report $report;

    public string $catatan = '';
    public string $statusProgress = 'dikerjakan';
    public $fotoBukti = null;

    public function mount(Report $report): void
    {
        // Pastikan laporan ini memang ditugaskan ke petugas yang login
        $assigned = ReportAssignment::where('report_id', $report->id)
            ->where('petugas_id', Auth::id())
            ->exists();
        abort_unless($assigned, 403);

        $this->report = $report->load(['kategori', 'progress.petugas', 'pelapor']);
    }

    /** Penugasan milik petugas ini untuk laporan tsb. */
    public function getAssignmentProperty(): ?ReportAssignment
    {
        return ReportAssignment::where('report_id', $this->report->id)
            ->where('petugas_id', Auth::id())
            ->first();
    }

    /** Sudah selesai bila laporan selesai atau penugasan selesai. */
    public function getSudahSelesaiProperty(): bool
    {
        return $this->report->status === 'selesai'
            || optional($this->assignment)->status === 'selesai';
    }

    public function simpanProgress(FonnteService $fonnte): void
    {
        // Cegah update bila sudah selesai
        if ($this->sudahSelesai) {
            session()->flash('ok', 'Laporan sudah diselesaikan.');
            return;
        }

        $this->validate([
            'catatan'        => 'required|string|max:1000',
            'statusProgress' => 'required|in:dikerjakan,selesai',
            'fotoBukti'      => 'nullable|image|max:4096',
        ], [], [
            'catatan'   => 'catatan',
            'fotoBukti' => 'foto bukti',
        ]);

        $path = $this->fotoBukti
            ? $this->fotoBukti->store('report-progress', 'public')
            : null;

        DB::transaction(function () use ($path) {
            ReportProgress::create([
                'report_id'       => $this->report->id,
                'petugas_id'      => Auth::id(),
                'catatan'         => $this->catatan,
                'foto_bukti'      => $path,
                'status_progress' => $this->statusProgress,
            ]);

            $assignment = $this->assignment;

            if ($this->statusProgress === 'selesai') {
                $assignment?->update(['status' => 'selesai']);
                $this->report->update(['status' => 'selesai']);
            } else {
                if ($assignment && $assignment->status === 'ditugaskan') {
                    $assignment->update(['status' => 'dikerjakan']);
                }
                if (in_array($this->report->status, ['ditugaskan', 'diverifikasi'])) {
                    $this->report->update(['status' => 'proses']);
                }
            }
        });

        // Kirim notifikasi WhatsApp ke pelapor (best-effort, tidak menggagalkan proses)
        $this->kirimWaPelapor($fonnte, $this->catatan, $this->statusProgress);

        $this->reset(['catatan', 'fotoBukti']);
        $this->statusProgress = 'dikerjakan';
        $this->report->refresh()->load(['kategori', 'progress.petugas', 'pelapor']);

        session()->flash('ok', 'Progress berhasil disimpan.');
    }

    /** Kirim update progress ke WhatsApp pelapor. */
    private function kirimWaPelapor(FonnteService $fonnte, string $catatan, string $statusProgress): void
    {
        $phone = $this->report->pelapor?->phone;
        if (! $phone) {
            return; // pelapor tidak punya nomor
        }

        $statusLabel = $statusProgress === 'selesai' ? 'Selesai' : 'Sedang Dikerjakan';

        try {
            $pesan = "Halo " . ($this->report->pelapor?->name ?? 'Pelapor') . ",\n\n"
                . "Ada pembaruan untuk laporan Anda di Niti Resik.\n"
                . "No. Tiket: " . ($this->report->tiket_no ?? '-') . "\n"
                . "Judul: " . $this->report->judul . "\n"
                . "Status: {$statusLabel}\n"
                . "Catatan petugas: {$catatan}\n\n"
                . "Terima kasih telah melapor. Pantau perkembangan di aplikasi Niti Resik.";

            $fonnte->sendWa($phone, $pesan);
        } catch (\Throwable $e) {
            Log::warning('Gagal kirim WA update progress ke pelapor', [
                'report_id' => $this->report->id,
                'error'     => $e->getMessage(),
            ]);
        }
    }

    public function render()
    {
        return view('livewire.petugas-lapangan.tugas-detail', [
            'assignment'   => $this->assignment,
            'sudahSelesai' => $this->sudahSelesai,
            'timeline'     => $this->report->progress->sortByDesc('created_at')->values(),
        ]);
    }
}