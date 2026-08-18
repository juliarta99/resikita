<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\StatusLaporan;
use App\Enums\StatusPenarikan;
use App\Enums\StatusPengajuanWilayah;
use App\Enums\StatusUmkm;
use App\Models\Laporan;
use App\Models\PenarikanSaldo;
use App\Models\PengajuanWilayah;
use App\Models\Umkm;
use App\Models\UmkmPenarikan;
use App\Models\User;
use App\Models\Wilayah;
use App\Services\Analitik\AnalitikService;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Dasbor platform.
 *
 * Berbeda dari dasbor pemerintahan: yang diukur di sini bukan kinerja
 * satu daerah, melainkan kesehatan sistem secara keseluruhan, berapa
 * wilayah yang sudah bergabung, berapa antrean persetujuan yang
 * menumpuk, dan seberapa jauh fitur suara benar-benar dipakai.
 *
 * Angka fitur suara sengaja ikut. Klaim inklusivitas yang tidak pernah
 * dihitung hanya akan menjadi kalimat di proposal.
 */
#[Title('Dasbor Platform')]
class Dashboard extends Component
{
    public function render(AnalitikService $analitik)
    {
        return view('livewire.admin.dashboard', [
            'totalPengguna' => User::query()->count(),
            'penggunaBaruBulanIni' => User::query()->where('created_at', '>=', now()->startOfMonth())->count(),

            'totalLaporan' => Laporan::query()->count(),
            'laporanAktif' => Laporan::query()->whereIn('status', StatusLaporan::aktif())->count(),

            'wilayahTerverifikasi' => Wilayah::query()->terverifikasi()->count(),
            'wilayahMenunggu' => Wilayah::query()->belumTerjangkau()->where('skor_prioritas', '>', 0)->count(),

            // Antrean persetujuan: pekerjaan admin yang paling nyata.
            'pengajuanMenunggu' => PengajuanWilayah::query()
                ->where('status', StatusPengajuanWilayah::Diajukan)->count(),
            'umkmMenunggu' => Umkm::query()->where('status', StatusUmkm::Menunggu)->count(),
            'penarikanMenunggu' => PenarikanSaldo::query()
                ->where('status', StatusPenarikan::Menunggu)->count(),
            'penarikanUmkmMenunggu' => UmkmPenarikan::query()
                ->where('status', StatusPenarikan::Menunggu)->count(),

            'fiturSuara' => $analitik->pemakaianFiturSuara(),

            'laporanTerbaru' => Laporan::query()->untukDaftar()->latest('id')->limit(6)->get(),
        ]);
    }
}
