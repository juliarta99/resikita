<?php

namespace App\Livewire\Umkm;

use App\Exports\TableExport;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.umkm')]
class Keuangan extends Component
{
    private const PAID = ['dibayar', 'dikemas', 'dikirim', 'selesai'];

    public string $dari = '';
    public string $sampai = '';

    public function mount()
    {
        $this->dari = now()->startOfMonth()->toDateString();
        $this->sampai = now()->toDateString();
    }

    private function umkmId(): int
    {
        return Auth::user()->umkm_id;
    }

    private function range(): array
    {
        return [Carbon::parse($this->dari)->startOfDay(), Carbon::parse($this->sampai)->endOfDay()];
    }

    private function pendapatan(): float
    {
        [$s, $e] = $this->range();

        return (float) OrderItem::whereHas('order', fn ($q) => $q->where('umkm_id', $this->umkmId())
            ->whereIn('status', self::PAID)->whereBetween('created_at', [$s, $e]))->sum('subtotal');
    }

    private function ongkir(): float
    {
        [$s, $e] = $this->range();

        return (float) Order::where('umkm_id', $this->umkmId())->whereIn('status', self::PAID)
            ->whereBetween('created_at', [$s, $e])->sum('ongkir');
    }

    private function jumlahPesanan(): int
    {
        [$s, $e] = $this->range();

        return Order::where('umkm_id', $this->umkmId())->whereIn('status', self::PAID)
            ->whereBetween('created_at', [$s, $e])->count();
    }

    private function dibatalkan(): int
    {
        [$s, $e] = $this->range();

        return Order::where('umkm_id', $this->umkmId())->where('status', 'dibatalkan')
            ->whereBetween('created_at', [$s, $e])->count();
    }

    private function perProduk()
    {
        [$s, $e] = $this->range();

        return OrderItem::whereHas('order', fn ($q) => $q->where('umkm_id', $this->umkmId())
            ->whereIn('status', self::PAID)->whereBetween('created_at', [$s, $e]))
            ->selectRaw('nama_snapshot, SUM(qty) as qty, SUM(subtotal) as revenue')
            ->groupBy('nama_snapshot')->orderByDesc('revenue')->get();
    }

    public function exportLabaRugi()
    {
        $pendapatan = $this->pendapatan();
        $ongkir = $this->ongkir();

        $rows = [
            ['Periode', $this->dari . ' s/d ' . $this->sampai],
            ['Pendapatan Penjualan (Rp)', $pendapatan],
            ['Ongkos Kirim Diterima (Rp)', $ongkir],
            ['Total Pemasukan (Rp)', $pendapatan + $ongkir],
            ['Harga Pokok Penjualan (belum dicatat)', 0],
            ['Laba Kotor (Rp)', $pendapatan],
            ['Beban Operasional (belum dicatat)', 0],
            ['Laba Bersih (Rp)', $pendapatan],
        ];

        return (new TableExport(['Keterangan', 'Nilai'], $rows, 'Laba Rugi'))
            ->download('laba-rugi-' . $this->dari . '-' . $this->sampai . '.xls');
    }

    public function exportPenjualan()
    {
        $rows = $this->perProduk()->map(fn ($p) => [$p->nama_snapshot, (int) $p->qty, (float) $p->revenue])->all();

        return (new TableExport(['Produk', 'Qty Terjual', 'Pendapatan (Rp)'], $rows, 'Penjualan'))
            ->download('penjualan-produk-' . $this->dari . '-' . $this->sampai . '.xls');
    }

    public function render()
    {
        $pendapatan = $this->pendapatan();
        $pesanan = $this->jumlahPesanan();

        return view('livewire.umkm.keuangan', [
            'ringkas' => [
                'pendapatan' => $pendapatan,
                'ongkir'     => $this->ongkir(),
                'pesanan'    => $pesanan,
                'rata'       => $pesanan > 0 ? $pendapatan / $pesanan : 0,
                'dibatalkan' => $this->dibatalkan(),
            ],
            'perProduk' => $this->perProduk(),
        ]);
    }
}