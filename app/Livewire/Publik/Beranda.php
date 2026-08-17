<?php

declare(strict_types=1);

namespace App\Livewire\Publik;

use App\Models\Artikel;
use App\Models\Produk;
use App\Services\Analitik\StatistikPublikService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Halaman muka Resikita.
 *
 * Pembacanya tiga jenis dan ketiganya harus menemukan pintunya dalam
 * sekali gulir: warga yang ingin melapor atau menyetor sampah, pejabat
 * daerah yang mempertimbangkan bergabung, dan pengunjung yang sekadar
 * ingin tahu apakah ini bekerja.
 *
 * Yang terakhir itu alasan angka dampak ditaruh di atas, bukan di
 * bawah. Klaim tanpa angka hanya menjadi kalimat pemasaran.
 */
#[Layout('components.layouts.publik')]
#[Title('Beranda')]
class Beranda extends Component
{
    public function render(StatistikPublikService $statistik)
    {
        return view('livewire.publik.beranda', [
            'ringkasan' => $statistik->ringkasan(),
            'fiturSuara' => $statistik->fiturSuara(),

            'artikelUnggulan' => Artikel::query()
                ->terbit()
                ->with('kategori:id,nama,slug')
                ->unggulan()
                ->latest('published_at')
                ->limit(3)
                ->get(),

            'artikelTerbaru' => Artikel::query()
                ->terbit()
                ->with('kategori:id,nama,slug')
                ->latest('published_at')
                ->limit(4)
                ->get(),

            'produkTerbaru' => Produk::query()
                ->tersedia()
                ->untukKatalog()
                ->whereHas('umkm', fn ($q) => $q->aktif())
                ->latest('id')
                ->limit(4)
                ->get(),
        ]);
    }
}
