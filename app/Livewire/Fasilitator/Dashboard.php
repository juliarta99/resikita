<?php

declare(strict_types=1);

namespace App\Livewire\Fasilitator;

use App\Models\Laporan;
use App\Models\LaporanTindakLanjut;
use App\Models\Wilayah;
use App\Services\Laporan\TindakLanjutService;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Dasbor Fasilitator Wilayah.
 *
 * Perannya berbeda dari panel lain: fasilitator tidak menangani laporan,
 * ia menjembatani laporan yang tidak punya siapa pun untuk menanganinya.
 * Karena itu angka yang penting di sini bukan "berapa yang selesai",
 * melainkan berapa laporan yang masih menggantung dan wilayah mana yang
 * paling mendesak untuk diajak bergabung.
 */
#[Title('Dasbor Fasilitator')]
class Dashboard extends Component
{
    public function render(TindakLanjutService $tindakLanjut)
    {
        $belumTerjangkau = $tindakLanjut->papanLaporanBelumTerjangkau();

        return view('livewire.fasilitator.dashboard', [
            'jumlahLaporan' => (clone $belumTerjangkau)->count(),

            // Laporan yang belum pernah dikontakkan ke dinas mana pun.
            // Inilah angka yang benar-benar menuntut tindakan hari ini.
            'belumDitindaklanjuti' => (clone $belumTerjangkau)
                ->whereDoesntHave('tindakLanjut')
                ->count(),

            'wilayahMenunggu' => Wilayah::query()
                ->where('skor_prioritas', '>', 0)
                ->belumTerjangkau()
                ->count(),

            'kontakBulanIni' => LaporanTindakLanjut::query()
                ->where('tanggal_kontak', '>=', now()->startOfMonth()->toDateString())
                ->count(),

            'prioritasTeratas' => $tindakLanjut->papanPrioritas(perHalaman: 5),

            'laporanTerbaru' => (clone $belumTerjangkau)
                ->whereDoesntHave('tindakLanjut')
                ->limit(6)
                ->get(),

            'kontakTerakhir' => LaporanTindakLanjut::query()
                ->with(['laporan:id,tiket,judul', 'fasilitator:id,name'])
                ->latest('tanggal_kontak')
                ->limit(5)
                ->get(),

            'totalLaporanSistem' => Laporan::query()->count(),
        ]);
    }
}
