<?php

namespace App\Livewire\Eksekutif;

use App\Exports\TableExport;
use App\Models\AiRecommendation;
use App\Models\BankSampah;
use App\Models\Report;
use App\Models\Tps;
use App\Models\Umkm;
use App\Models\User;
use App\Models\WasteDeposit;
use App\Services\Integration\GeminiService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.eksekutif')]
class EksekutifDashboard extends Component
{
    use ResolvesScope;

    public bool $aiError = false;
    public string $aiErrorMsg = '';

    private function stats(array $scope): array
    {
        $banjarIds = $scope['banjarIds'];
        $bankSampahIds = BankSampah::whereIn('banjar_id', $banjarIds)->pluck('id');

        $dep = WasteDeposit::whereIn('bank_sampah_id', $bankSampahIds)
            ->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);

        $rep = Report::whereIn('banjar_id', $banjarIds);

        return [
            'warga'         => User::role('masyarakat')->whereIn('banjar_id', $banjarIds)->count(),
            'bankSampah'    => $bankSampahIds->count(),
            'tps'           => Tps::whereIn('banjar_id', $banjarIds)->count(),
            'umkm'          => Umkm::whereIn('banjar_id', $banjarIds)->where('status', 'aktif')->count(),
            'setoranNilai'  => (float) (clone $dep)->sum('total_nilai'),
            'setoranBerat'  => (float) (clone $dep)->sum('total_berat'),
            'setoranJumlah' => (clone $dep)->count(),
            'lapTotal'      => (clone $rep)->count(),
            'lapMenunggu'   => (clone $rep)->where('status', 'menunggu')->count(),
            'lapProses'     => (clone $rep)->whereIn('status', ['diverifikasi', 'ditugaskan', 'proses'])->count(),
            'lapSelesai'    => (clone $rep)->where('status', 'selesai')->count(),
            'lapDitolak'    => (clone $rep)->where('status', 'ditolak')->count(),
        ];
    }

    private function tren(array $scope): array
    {
        $bankSampahIds = BankSampah::whereIn('banjar_id', $scope['banjarIds'])->pluck('id');
        $labels = [];
        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $labels[] = $m->format('M Y');
            $data[] = (float) WasteDeposit::whereIn('bank_sampah_id', $bankSampahIds)
                ->whereYear('created_at', $m->year)->whereMonth('created_at', $m->month)->sum('total_nilai');
        }

        return ['labels' => $labels, 'data' => $data];
    }

    private function currentRec(array $scope): ?AiRecommendation
    {
        return AiRecommendation::where('scope_type', $scope['type'])
            ->where('scope_id', $scope['id'])
            ->where('periode', now()->toDateString())
            ->latest()->first();
    }

    public function generateAi(GeminiService $gemini)
    {
        $this->aiError = false;
        $scope = $this->scope();
        $s = $this->stats($scope);

        $context = "Wilayah: {$scope['label']}\n"
            . "Warga terdaftar: {$s['warga']}\n"
            . "Bank sampah: {$s['bankSampah']}, TPS: {$s['tps']}, UMKM aktif: {$s['umkm']}\n"
            . "Setoran sampah bulan ini: {$s['setoranJumlah']} transaksi, {$s['setoranBerat']} kg, senilai Rp " . number_format($s['setoranNilai'], 0, ',', '.') . "\n"
            . "Laporan sampah: total {$s['lapTotal']} (menunggu {$s['lapMenunggu']}, dalam proses {$s['lapProses']}, selesai {$s['lapSelesai']}, ditolak {$s['lapDitolak']}).";

        try {
            $hasil = $gemini->recommend($context);
        } catch (\Throwable $e) {
            $this->aiError = true;
            $this->aiErrorMsg = 'Gagal menghubungi layanan AI. Periksa konfigurasi API atau coba lagi nanti.';
            return;
        }

        AiRecommendation::updateOrCreate(
            ['scope_type' => $scope['type'], 'scope_id' => $scope['id'], 'periode' => now()->toDateString()],
            ['konten' => $hasil, 'raw_response' => ['text' => $hasil], 'generated_by' => Auth::id()]
        );
    }

    public function exportRekomendasi()
    {
        $scope = $this->scope();
        $rec = $this->currentRec($scope);
        if (! $rec) {
            return null;
        }

        $isi = "REKOMENDASI PRIORITAS NITI RESIK\n"
            . "Wilayah : {$scope['label']}\n"
            . "Tanggal : " . now()->format('d M Y H:i') . "\n"
            . str_repeat('=', 50) . "\n\n"
            . $rec->konten . "\n";

        return response()->streamDownload(
            fn () => print($isi),
            'rekomendasi-' . $scope['type'] . '-' . now()->format('Ymd') . '.txt',
            ['Content-Type' => 'text/plain']
        );
    }

    public function exportStatistik()
    {
        $scope = $this->scope();
        $s = $this->stats($scope);

        $rows = [
            ['Wilayah', $scope['label']],
            ['Periode', now()->format('d M Y')],
            ['Warga terdaftar', $s['warga']],
            ['Bank sampah', $s['bankSampah']],
            ['TPS', $s['tps']],
            ['UMKM aktif', $s['umkm']],
            ['Setoran bulan ini (Rp)', $s['setoranNilai']],
            ['Setoran bulan ini (kg)', $s['setoranBerat']],
            ['Jumlah transaksi setoran', $s['setoranJumlah']],
            ['Laporan total', $s['lapTotal']],
            ['Laporan menunggu', $s['lapMenunggu']],
            ['Laporan diproses', $s['lapProses']],
            ['Laporan selesai', $s['lapSelesai']],
            ['Laporan ditolak', $s['lapDitolak']],
        ];

        return (new TableExport(['Metrik', 'Nilai'], $rows, 'Statistik'))->download('statistik-' . $scope['type'] . '-' . now()->format('Ymd') . '.xls');
    }

    public function render()
    {
        $scope = $this->scope();
        $stats = $this->stats($scope);
        $tren = $this->tren($scope);

        return view('livewire.eksekutif.dashboard', [
            'scopeLabel'  => $scope['label'],
            'stats'       => $stats,
            'trenLabels'  => $tren['labels'],
            'trenData'    => $tren['data'],
            'lapData'     => [$stats['lapMenunggu'], $stats['lapProses'], $stats['lapSelesai'], $stats['lapDitolak']],
            'rekomendasi' => $this->currentRec($scope),
        ]);
    }
}