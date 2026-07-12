<?php

namespace App\Livewire\Umkm;

use App\Models\AiRecommendation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Umkm;
use App\Services\Integration\GeminiService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.umkm')]
class Rekomendasi extends Component
{
    private const PAID = ['dibayar', 'dikemas', 'dikirim', 'selesai'];

    public bool $aiError = false;
    public string $aiErrorMsg = '';

    private function umkmId(): int
    {
        return Auth::user()->umkm_id;
    }

    private function currentRec(): ?AiRecommendation
    {
        return AiRecommendation::where('scope_type', 'umkm')
            ->where('scope_id', $this->umkmId())
            ->where('periode', now()->toDateString())
            ->latest()->first();
    }

    public function generateAi(GeminiService $gemini)
    {
        $this->aiError = false;
        $umkmId = $this->umkmId();
        $umkm = Umkm::find($umkmId);

        $pendapatanBln = (float) OrderItem::whereHas('order', fn ($q) => $q->where('umkm_id', $umkmId)
            ->whereIn('status', self::PAID)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year))
            ->sum('subtotal');

        $top = OrderItem::whereHas('order', fn ($q) => $q->where('umkm_id', $umkmId)->whereIn('status', self::PAID))
            ->selectRaw('nama_snapshot, SUM(qty) as qty')->groupBy('nama_snapshot')->orderByDesc('qty')->take(5)->get();

        $soldIds = OrderItem::whereHas('order', fn ($q) => $q->where('umkm_id', $umkmId)->whereIn('status', self::PAID))
            ->pluck('product_id')->unique();

        $stokRendah = Product::where('umkm_id', $umkmId)->where('is_active', true)->where('stok', '<', 5)->pluck('nama');
        $belumLaku = Product::where('umkm_id', $umkmId)->where('is_active', true)->whereNotIn('id', $soldIds)->pluck('nama');

        $context = "Nama usaha: {$umkm?->nama}\n"
            . 'Jumlah produk: ' . Product::where('umkm_id', $umkmId)->count() . ' (aktif: ' . Product::where('umkm_id', $umkmId)->where('is_active', true)->count() . ")\n"
            . 'Total pesanan: ' . Order::where('umkm_id', $umkmId)->count() . "\n"
            . 'Pendapatan bulan ini: Rp ' . number_format($pendapatanBln, 0, ',', '.') . "\n"
            . 'Produk terlaris: ' . ($top->map(fn ($t) => "{$t->nama_snapshot} ({$t->qty})")->implode(', ') ?: '-') . "\n"
            . 'Produk stok menipis (<5): ' . ($stokRendah->implode(', ') ?: '-') . "\n"
            . 'Produk belum terjual: ' . ($belumLaku->take(10)->implode(', ') ?: '-');

        try {
            $hasil = $gemini->recommend($context);
        } catch (\Throwable $e) {
            $this->aiError = true;
            $this->aiErrorMsg = 'Gagal menghubungi layanan AI. Periksa konfigurasi API atau coba lagi nanti.';
            return;
        }

        AiRecommendation::updateOrCreate(
            ['scope_type' => 'umkm', 'scope_id' => $umkmId, 'periode' => now()->toDateString()],
            ['konten' => $hasil, 'raw_response' => ['text' => $hasil], 'generated_by' => Auth::id()]
        );
    }

    public function exportRekomendasi()
    {
        $rec = $this->currentRec();
        if (! $rec) {
            return null;
        }

        $umkm = Umkm::find($this->umkmId());
        $isi = "REKOMENDASI AI NITI RESIK — UMKM\n"
            . "Usaha  : {$umkm?->nama}\n"
            . 'Tanggal: ' . now()->format('d M Y H:i') . "\n"
            . str_repeat('=', 50) . "\n\n" . $rec->konten . "\n";

        return response()->streamDownload(
            fn () => print($isi),
            'rekomendasi-umkm-' . now()->format('Ymd') . '.txt',
            ['Content-Type' => 'text/plain'],
        );
    }

    public function render()
    {
        return view('livewire.umkm.rekomendasi', ['rekomendasi' => $this->currentRec()]);
    }
}