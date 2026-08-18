<?php

declare(strict_types=1);

namespace App\Livewire\Publik;

use App\Enums\TipeArtikel;
use App\Models\Artikel;
use App\Models\ArtikelKategori;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Daftar artikel literasi lingkungan.
 *
 * Terbuka tanpa akun. Literasi yang disembunyikan di balik pendaftaran
 * berhenti menjadi literasi, orang yang paling perlu membacanya justru
 * yang paling enggan membuat akun.
 */
#[Layout('components.layouts.publik')]
#[Title('Literasi Lingkungan')]
class ArtikelIndex extends Component
{
    use WithPagination;

    #[Url(as: 'kategori', except: '')]
    public string $kategori = '';

    #[Url(as: 'tipe', except: '')]
    public string $tipe = '';

    #[Url(as: 'q', except: '')]
    public string $cari = '';

    public function updated(string $properti): void
    {
        if ($properti !== 'page') {
            $this->resetPage();
        }
    }

    public function render()
    {
        $query = Artikel::query()
            ->terbit()
            ->with('kategori:id,nama,slug')
            ->latest('published_at');

        if ($this->kategori !== '') {
            $query->whereHas('kategori', fn ($q) => $q->where('slug', $this->kategori));
        }

        if ($this->tipe !== '') {
            $query->where('tipe', $this->tipe);
        }

        if (trim($this->cari) !== '') {
            $query->where('judul', 'like', '%'.trim($this->cari).'%');
        }

        return view('livewire.publik.artikel-index', [
            'daftar' => $query->paginate(9),
            'kategoriTersedia' => ArtikelKategori::query()
                ->withCount(['artikel' => fn ($q) => $q->terbit()])
                ->orderBy('nama')
                ->get(),
            'tipeTersedia' => TipeArtikel::options(),
        ]);
    }
}
