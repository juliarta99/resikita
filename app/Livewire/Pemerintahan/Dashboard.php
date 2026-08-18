<?php

declare(strict_types=1);

namespace App\Livewire\Pemerintahan;

use App\Models\Laporan;
use App\Services\Analitik\AnalitikService;
use App\Services\Wilayah\WilayahScopeService;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Dasbor pemerintahan, dipakai tiga role sekaligus.
 *
 * Admin provinsi, admin kabupaten, dan kepala desa melihat layar yang
 * sama. Yang berbeda hanyalah data yang boleh mereka lihat, dan itu
 * ditentukan sepenuhnya oleh WilayahScopeService dari `users.wilayah_id`
 * serta role, bukan oleh percabangan di komponen ini.
 *
 * Tidak ada satu pun filter wilayah yang ditulis di sini. Kalau suatu
 * saat ada, itu tanda logikanya salah tempat (CLAUDE.md 9.5).
 */
#[Title('Dasbor')]
class Dashboard extends Component
{
    /** Rentang waktu yang sedang ditampilkan, dalam hari. */
    public int $rentang = 30;

    public function ubahRentang(int $hari): void
    {
        $this->rentang = in_array($hari, [7, 30, 90, 365], true) ? $hari : 30;
    }

    public function render(AnalitikService $analitik, WilayahScopeService $scope)
    {
        $pengguna = auth()->user();
        $sejak = now()->subDays($this->rentang)->toDateTimeString();

        return view('livewire.pemerintahan.dashboard', [
            'ringkasan' => $analitik->ringkasanLaporan($pengguna, $sejak),
            'perStatus' => $analitik->laporanPerStatus($pengguna),
            'perKategori' => $analitik->laporanPerKategori($pengguna, 5),
            'perWilayah' => $analitik->laporanPerWilayahAnak($pengguna),
            'dampak' => $analitik->dampakBankSampah($pengguna, $sejak),

            // Antrean kerja: laporan yang menunggu tindakan hari ini.
            // Diletakkan di dasbor karena inilah alasan sebagian besar
            // pengguna membuka panel ini sama sekali.
            'perluTindakan' => $scope
                ->applyLaporan(Laporan::query(), $pengguna)
                ->menungguTindakan()
                ->untukDaftar()
                ->latest('id')
                ->limit(5)
                ->get(),

            'wilayah' => $pengguna->wilayah,
        ]);
    }
}
