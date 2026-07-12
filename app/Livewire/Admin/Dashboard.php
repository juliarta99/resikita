<?php

namespace App\Livewire\Admin;

use App\Models\BankSampah;
use App\Models\BanjarDinas;
use App\Models\Kecamatan;
use App\Models\Report;
use App\Models\Tps;
use App\Models\Umkm;
use App\Models\User;
use App\Models\WasteDeposit;
use App\Models\Withdrawal;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Dashboard extends Component
{
    private function markers(): array
    {
        $out = [];
        foreach (Tps::whereNotNull('lat')->whereNotNull('lng')->get() as $t) {
            $out[] = ['t' => 'tps', 'n' => $t->nama, 'lat' => (float) $t->lat, 'lng' => (float) $t->lng];
        }
        foreach (BankSampah::whereNotNull('lat')->whereNotNull('lng')->get() as $b) {
            $out[] = ['t' => 'bank_sampah', 'n' => $b->nama, 'lat' => (float) $b->lat, 'lng' => (float) $b->lng];
        }
        foreach (Umkm::whereNotNull('lat')->whereNotNull('lng')->get() as $m) {
            $out[] = ['t' => 'umkm', 'n' => $m->nama, 'lat' => (float) $m->lat, 'lng' => (float) $m->lng];
        }
        foreach (Report::whereNotNull('lat')->whereNotNull('lng')->whereNotIn('status', ['selesai', 'ditolak'])->get() as $r) {
            $out[] = ['t' => 'laporan', 'n' => $r->judul, 'lat' => (float) $r->lat, 'lng' => (float) $r->lng];
        }

        return $out;
    }

    private function tren(): array
    {
        $labels = [];
        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $labels[] = $m->format('M Y');
            $data[] = (float) WasteDeposit::whereYear('created_at', $m->year)->whereMonth('created_at', $m->month)->sum('total_nilai');
        }

        return ['labels' => $labels, 'data' => $data];
    }

    public function render()
    {
        $tren = $this->tren();

        return view('livewire.admin.dashboard', [
            'stat' => [
                'pengguna'   => User::count(),
                'masyarakat' => User::role('masyarakat')->count(),
                'kecamatan'  => Kecamatan::count(),
                'banjar'     => BanjarDinas::count(),
                'tps'        => Tps::count(),
                'bankSampah' => BankSampah::count(),
                'umkm'       => Umkm::where('status', 'aktif')->count(),
                'setoranBln' => (float) WasteDeposit::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('total_nilai'),
            ],
            'perluTindakan' => [
                'umkmMenunggu'      => Umkm::where('status', 'menunggu')->count(),
                'penarikanMenunggu' => Withdrawal::where('status', 'menunggu')->count(),
                'laporanMenunggu'   => Report::where('status', 'menunggu')->count(),
            ],
            'trenLabels' => $tren['labels'],
            'trenData'   => $tren['data'],
            'lapData'    => [
                Report::where('status', 'menunggu')->count(),
                Report::whereIn('status', ['diverifikasi', 'ditugaskan', 'proses'])->count(),
                Report::where('status', 'selesai')->count(),
                Report::where('status', 'ditolak')->count(),
            ],
            'markers' => $this->markers(),
        ]);
    }
}