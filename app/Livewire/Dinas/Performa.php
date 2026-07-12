<?php

namespace App\Livewire\Dinas;

use App\Exports\TableExport;
use App\Models\Report;
use App\Models\ReportAssignment;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.dinas')]
class Performa extends Component
{
    public string $dari = '';
    public string $sampai = '';

    public function mount()
    {
        $this->dari = now()->startOfMonth()->toDateString();
        $this->sampai = now()->toDateString();
    }

    private function range(): array
    {
        return [
            Carbon::parse($this->dari)->startOfDay(),
            Carbon::parse($this->sampai)->endOfDay(),
        ];
    }

    private function perKategori()
    {
        [$s, $e] = $this->range();

        return Report::whereBetween('created_at', [$s, $e])->with('kategori')->get()
            ->groupBy(fn ($r) => $r->kategori?->nama ?? 'Lainnya')
            ->map(fn ($g, $nama) => ['nama' => $nama, 'total' => $g->count(), 'selesai' => $g->where('status', 'selesai')->count()])
            ->sortByDesc('total')->values();
    }

    private function perPetugas()
    {
        [$s, $e] = $this->range();

        return ReportAssignment::whereBetween('assigned_at', [$s, $e])->with(['petugas', 'report'])->get()
            ->groupBy(fn ($a) => $a->petugas?->name ?? '—')
            ->map(fn ($g, $nama) => [
                'nama'       => $nama,
                'ditugaskan' => $g->count(),
                'selesai'    => $g->filter(fn ($a) => $a->report?->status === 'selesai')->count(),
            ])->sortByDesc('ditugaskan')->values();
    }

    public function exportKategori()
    {
        $rows = $this->perKategori()->map(fn ($k) => [$k['nama'], $k['total'], $k['selesai']])->all();

        return (new TableExport(['Kategori', 'Total Laporan', 'Selesai'], $rows, 'Performa Kategori'))->download('performa-kategori-' . $this->dari . '-' . $this->sampai . '.xls');
    }

    public function exportPetugas()
    {
        $rows = $this->perPetugas()->map(fn ($p) => [$p['nama'], $p['ditugaskan'], $p['selesai']])->all();

        return (new TableExport(['Petugas', 'Ditugaskan', 'Selesai'], $rows, 'Performa Petugas'))->download('performa-petugas-' . $this->dari . '-' . $this->sampai . '.xls');
    }

    public function render()
    {
        [$s, $e] = $this->range();
        $base = Report::whereBetween('created_at', [$s, $e]);

        $masuk = (clone $base)->count();
        $selesai = (clone $base)->where('status', 'selesai')->count();

        return view('livewire.dinas.performa', [
            'stat' => [
                'masuk'    => $masuk,
                'menunggu' => (clone $base)->where('status', 'menunggu')->count(),
                'proses'   => (clone $base)->whereIn('status', ['diverifikasi', 'ditugaskan', 'proses'])->count(),
                'selesai'  => $selesai,
                'ditolak'  => (clone $base)->where('status', 'ditolak')->count(),
                'rate'     => $masuk > 0 ? round($selesai / $masuk * 100) : 0,
            ],
            'perKategori' => $this->perKategori(),
            'perPetugas'  => $this->perPetugas(),
        ]);
    }
}