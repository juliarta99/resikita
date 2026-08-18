<?php

declare(strict_types=1);

namespace App\Livewire\Pemerintahan;

use App\Livewire\Concerns\MemberiUmpanBalik;
use App\Services\Analitik\AnalitikService;
use App\Services\Analitik\RekomendasiService;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Analitik wilayah: tren, sebaran, dampak, dan rekomendasi AI.
 *
 * Semua angka datang dari AnalitikService, yang membatasi setiap query
 * lewat WilayahScopeService. Analitik justru tempat kebocoran lintas
 * daerah paling mudah lolos, sebuah agregat tidak menampilkan barisnya
 * satu per satu, jadi angka yang diam-diam mencakup kabupaten tetangga
 * terlihat wajar sampai ada yang membandingkannya dengan data resmi.
 */
#[Title('Analitik Wilayah')]
class Analitik extends Component
{
    use MemberiUmpanBalik;

    public int $bulan = 12;

    public bool $sedangMenyusun = false;

    public function susunRekomendasi(RekomendasiService $rekomendasi): void
    {
        $this->sedangMenyusun = true;

        $this->jalankan(
            fn () => $rekomendasi->untukWilayah(auth()->user(), paksaBaru: true),
            'Rekomendasi baru selesai disusun.',
        );

        $this->sedangMenyusun = false;
    }

    public function render(AnalitikService $analitik, RekomendasiService $rekomendasi)
    {
        $pengguna = auth()->user();

        return view('livewire.pemerintahan.analitik', [
            'tren' => $analitik->trenLaporanBulanan($pengguna, $this->bulan),
            'perKategori' => $analitik->laporanPerKategori($pengguna, 8),
            'perWilayah' => $analitik->laporanPerWilayahAnak($pengguna),
            'dampak' => $analitik->dampakBankSampah($pengguna),
            'titik' => $analitik->titikPetaLaporan($pengguna, 500),
            'rekomendasi' => $rekomendasi->terbaru($pengguna),
            'wilayah' => $pengguna->wilayah,
        ]);
    }
}
