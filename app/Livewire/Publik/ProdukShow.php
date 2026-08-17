<?php

declare(strict_types=1);

namespace App\Livewire\Publik;

use App\Models\Produk;
use App\Models\Ulasan;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Halaman satu produk.
 *
 * Foto yang tampil selalu foto asli dari penjual. Sampul hasil Asisten
 * Konten UMKM boleh menempel teks dan bingkai di atasnya, tapi tidak
 * pernah menggantikan citra produknya dengan gambar hasil generate,
 * itu perlindungan konsumen, bukan pilihan estetika (CLAUDE.md 10.3).
 */
#[Layout('components.layouts.publik')]
class ProdukShow extends Component
{
    public Produk $produk;

    public function mount(Produk $produk): void
    {
        $this->authorize('view', $produk);

        $this->produk = $produk;
    }

    public function render()
    {
        $this->produk->loadMissing(['umkm.wilayah', 'kategori', 'foto']);
        $this->produk->loadCount('ulasan');
        $this->produk->loadAvg('ulasan as rating_rata', 'rating');

        return view('livewire.publik.produk-show', [
            'ulasan' => Ulasan::query()
                ->where('produk_id', $this->produk->id)
                ->with('user:id,name')
                ->latest('id')
                ->limit(5)
                ->get(),

            'lainnya' => Produk::query()
                ->tersedia()
                ->untukKatalog()
                ->where('umkm_id', $this->produk->umkm_id)
                ->whereKeyNot($this->produk->id)
                ->limit(4)
                ->get(),
        ])->title($this->produk->nama);
    }
}
