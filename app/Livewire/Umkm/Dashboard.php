<?php

namespace App\Livewire\Umkm;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.umkm')]
class Dashboard extends Component
{
    private const PAID = ['dibayar', 'dikemas', 'dikirim', 'selesai'];

    private function umkmId(): int
    {
        return Auth::user()->umkm_id;
    }

    private function pendapatanBulan(int $tahun, int $bulan): float
    {
        return (float) OrderItem::whereHas('order', fn ($q) => $q->where('umkm_id', $this->umkmId())
            ->whereIn('status', self::PAID)->whereYear('created_at', $tahun)->whereMonth('created_at', $bulan))
            ->sum('subtotal');
    }

    public function render()
    {
        $umkmId = $this->umkmId();

        $labels = [];
        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $labels[] = $m->format('M Y');
            $data[] = $this->pendapatanBulan($m->year, $m->month);
        }

        $top = OrderItem::whereHas('order', fn ($q) => $q->where('umkm_id', $umkmId)->whereIn('status', self::PAID))
            ->selectRaw('nama_snapshot, SUM(qty) as qty, SUM(subtotal) as revenue')
            ->groupBy('nama_snapshot')->orderByDesc('qty')->take(5)->get();

        return view('livewire.umkm.dashboard', [
            'stat' => [
                'produk'      => Product::where('umkm_id', $umkmId)->count(),
                'produkAktif' => Product::where('umkm_id', $umkmId)->where('is_active', true)->count(),
                'pesanan'     => Order::where('umkm_id', $umkmId)->count(),
                'pesananBln'  => Order::where('umkm_id', $umkmId)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
                'pendapatan'  => $this->pendapatanBulan(now()->year, now()->month),
                'perluProses' => Order::where('umkm_id', $umkmId)->where('status', 'dibayar')->count(),
            ],
            'trenLabels' => $labels,
            'trenData'   => $data,
            'top'        => $top,
            'terbaru'    => Order::where('umkm_id', $umkmId)->latest()->take(6)->get(),
        ]);
    }
}