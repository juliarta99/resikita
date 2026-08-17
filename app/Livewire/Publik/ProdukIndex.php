<?php

declare(strict_types=1);

namespace App\Livewire\Publik;

use App\Models\Produk;
use App\Models\ProdukKategori;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Katalog produk daur ulang, mode jelajah.
 *
 * Hanya menampilkan. Keranjang dan checkout ada di aplikasi ponsel,
 * karena keduanya menyangkut alamat pengiriman dan pembayaran, dua hal
 * yang menuntut akun dan verifikasi, bukan sekadar melihat-lihat.
 */
#[Layout('components.layouts.publik')]
#[Title('Marketplace Produk Daur Ulang')]
class ProdukIndex extends Component
{
    use WithPagination;

    #[Url(as: 'kategori', except: '')]
    public string $kategori = '';

    #[Url(as: 'q', except: '')]
    public string $cari = '';

    #[Url(as: 'urut', except: 'terbaru')]
    public string $urut = 'terbaru';

    public function updated(string $properti): void
    {
        if ($properti !== 'page') {
            $this->resetPage();
        }
    }

    public function render()
    {
        $query = Produk::query()
            ->tersedia()
            ->untukKatalog()
            // Produk milik toko yang belum diverifikasi tidak boleh tampil.
            // Itu perlindungan pembeli, bukan kerapian data.
            ->whereHas('umkm', fn ($q) => $q->aktif());

        if ($this->kategori !== '') {
            $query->whereHas('kategori', fn ($q) => $q->where('slug', $this->kategori));
        }

        if (trim($this->cari) !== '') {
            $kata = trim($this->cari);
            $query->where(fn ($q) => $q
                ->where('nama', 'like', "%{$kata}%")
                ->orWhere('bahan_baku', 'like', "%{$kata}%"));
        }

        match ($this->urut) {
            'termurah' => $query->orderBy('harga'),
            'termahal' => $query->orderByDesc('harga'),
            'rating' => $query->orderByDesc('rating_rata'),
            default => $query->latest('id'),
        };

        return view('livewire.publik.produk-index', [
            'daftar' => $query->paginate(12),
            'kategoriTersedia' => ProdukKategori::query()
                ->withCount(['produk' => fn ($q) => $q->tersedia()])
                ->orderBy('nama')
                ->get(),
        ]);
    }
}
