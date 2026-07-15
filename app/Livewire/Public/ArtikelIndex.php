<?php

namespace App\Livewire\Public;

use App\Models\Article;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.public')]
class ArtikelIndex extends Component
{
    use WithPagination;

    public string $tipeFilter = '';
    public string $search = '';

    public array $tipeList = ['artikel' => 'Artikel', 'panduan' => 'Panduan', 'tutorial' => 'Tutorial', 'jurnal' => 'Jurnal'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingTipeFilter()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Article::with('author')
            ->where('status', 'published')
            ->latest('published_at');

        if ($this->tipeFilter !== '') {
            $query->where('tipe', $this->tipeFilter);
        }
        if ($this->search !== '') {
            $query->where('judul', 'like', '%' . $this->search . '%');
        }

        return view('livewire.public.artikel-index', [
            'artikel' => $query->paginate(9),
        ]);
    }
}