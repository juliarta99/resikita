<?php

declare(strict_types=1);

namespace App\Livewire\Publik;

use App\Models\Artikel;
use App\Services\Konten\TeksBacaService;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Satu artikel literasi, lengkap dengan pemutar suaranya.
 *
 * ## Teks yang dibacakan datang dari peladen
 *
 * Pembersihan markdown dilakukan TeksBacaService dan hasilnya disimpan
 * di `artikel.teks_baca`. Halaman ini hanya menyerahkannya ke pembaca
 * suara peramban. Kalau klien membersihkan sendiri, web dan aplikasi
 * ponsel perlahan membacakan kalimat yang berbeda dari artikel yang
 * sama, dan tidak ada yang menyadarinya sampai ada yang mendengarkan
 * keduanya (CLAUDE.md 10.4).
 *
 * ## Dua penghitung yang berbeda
 *
 * `dilihat` naik saat halaman dibuka; `didengarkan` naik hanya ketika
 * pemutaran benar-benar dimulai. Menggabungkannya akan membuat angka
 * pemakaian fitur suara ikut naik setiap kali seseorang sekadar
 * membuka halaman, dan klaim inklusivitas kehilangan dasarnya.
 */
#[Layout('components.layouts.publik')]
class ArtikelShow extends Component
{
    public Artikel $artikel;

    public function mount(Artikel $artikel): void
    {
        $this->authorize('view', $artikel);

        $this->artikel = $artikel;
        $this->artikel->catatDilihat();
    }

    /** Dipanggil sekali dari peramban saat pemutaran suara dimulai. */
    public function catatDidengarkan(): void
    {
        $this->artikel->catatDidengarkan();
    }

    public function render(TeksBacaService $teksBaca)
    {
        $this->artikel->loadMissing(['kategori', 'penulis']);

        return view('livewire.publik.artikel-show', [
            'teksBaca' => $teksBaca->untukArtikel($this->artikel),

            'lainnya' => Artikel::query()
                ->terbit()
                ->with('kategori:id,nama,slug')
                ->whereKeyNot($this->artikel->id)
                ->when(
                    $this->artikel->kategori_id !== null,
                    fn ($q) => $q->where('kategori_id', $this->artikel->kategori_id),
                )
                ->latest('published_at')
                ->limit(3)
                ->get(),
        ])->title($this->artikel->judul);
    }
}
