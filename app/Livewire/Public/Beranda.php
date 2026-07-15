<?php

namespace App\Livewire\Public;

use App\Models\BankSampah;
use App\Models\Report;
use App\Models\Tps;
use App\Models\Umkm;
use App\Models\WasteDeposit;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public')]
class Beranda extends Component
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
        foreach (Umkm::where('status', 'aktif')->whereNotNull('lat')->whereNotNull('lng')->get() as $m) {
            $out[] = ['t' => 'umkm', 'n' => $m->nama, 'lat' => (float) $m->lat, 'lng' => (float) $m->lng];
        }

        return $out;
    }

    public function render()
    {
        return view('livewire.public.beranda', [
            'stat' => [
                'bankSampah'    => BankSampah::count(),
                'tps'           => Tps::count(),
                'umkm'          => Umkm::where('status', 'aktif')->count(),
                'sampahKg'      => (float) WasteDeposit::sum('total_berat'),
                'laporanTuntas' => Report::where('status', 'selesai')->count(),
            ],
            'umkms'    => Umkm::where('status', 'aktif')->withCount('products')->latest()->take(6)->get(),
            'laporans' => Report::with('kategori')->whereIn('status', ['proses', 'ditugaskan', 'selesai'])->latest()->take(4)->get(),
            'markers'  => $this->markers(),
        ]);
    }
}