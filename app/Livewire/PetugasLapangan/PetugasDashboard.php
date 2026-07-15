<?php

namespace App\Livewire\PetugasLapangan;

use App\Models\ReportAssignment;
use App\Models\ReportProgress;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.petugas')]
class PetugasDashboard extends Component
{
    public function render()
    {
        $uid = Auth::id();

        $base = ReportAssignment::where('petugas_id', $uid);

        // Ringkasan
        $aktif = (clone $base)->whereIn('status', ['ditugaskan', 'dikerjakan'])->count();
        $selesaiBulan = (clone $base)->where('status', 'selesai')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->count();
        $totalDitangani = (clone $base)->count();
        $selesaiTotal = (clone $base)->where('status', 'selesai')->count();

        // Tren 7 hari terakhir: jumlah update progress oleh petugas ini per hari
        $trenLabels = [];
        $trenData = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = now()->subDays($i);
            $trenLabels[] = $d->format('d M');
            $trenData[] = ReportProgress::where('petugas_id', $uid)
                ->whereDate('created_at', $d->toDateString())
                ->count();
        }

        // Tugas terbaru
        $recent = ReportAssignment::with(['report.kategori'])
            ->where('petugas_id', $uid)
            ->latest('assigned_at')
            ->latest('id')
            ->take(8)
            ->get();

        return view('livewire.petugas-lapangan.dashboard', [
            'aktif'          => $aktif,
            'selesaiBulan'   => $selesaiBulan,
            'totalDitangani' => $totalDitangani,
            'selesaiTotal'   => $selesaiTotal,
            'recent'         => $recent,
            'trenLabels'     => $trenLabels,
            'trenData'       => $trenData,
        ]);
    }
}