<?php

namespace App\Livewire\Tps;

use App\Models\Tps;
use App\Models\TpsMember;
use App\Models\TpsSubscription;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.tps')]
class Dashboard extends Component
{
    private function tpsId(): int
    {
        return Auth::user()->tps_id;
    }

    private function lunasPeriode(string $periode): float
    {
        return (float) TpsSubscription::whereHas('member', fn ($q) => $q->where('tps_id', $this->tpsId()))
            ->where('periode', $periode)->where('status', 'lunas')->sum('jumlah');
    }

    public function render()
    {
        $tpsId = $this->tpsId();
        $tps = Tps::find($tpsId);
        $periode = now()->format('Y-m');

        $subBulan = TpsSubscription::whereHas('member', fn ($q) => $q->where('tps_id', $tpsId))->where('periode', $periode);

        $labels = [];
        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $labels[] = $m->format('M Y');
            $data[] = $this->lunasPeriode($m->format('Y-m'));
        }

        return view('livewire.tps.dashboard', [
            'tps' => $tps,
            'stat' => [
                'nasabahAktif'  => TpsMember::where('tps_id', $tpsId)->where('status', 'aktif')->count(),
                'nasabahTotal'  => TpsMember::where('tps_id', $tpsId)->count(),
                'lunasBln'      => (float) (clone $subBulan)->where('status', 'lunas')->sum('jumlah'),
                'menungguBln'   => (clone $subBulan)->where('status', 'menunggu')->count(),
                'lunasBlnCount' => (clone $subBulan)->where('status', 'lunas')->count(),
            ],
            'trenLabels' => $labels,
            'trenData'   => $data,
        ]);
    }
}