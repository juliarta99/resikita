<?php

namespace App\Livewire\Public;

use App\Models\Article;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public')]
class ArtikelShow extends Component
{
    public Article $article;

    public function mount(string $slug)
    {
        $this->article = Article::with('author')
            ->where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();
    }

    public function render()
    {
        $konten = $this->article->konten;

        // Konten baru = HTML (dari editor). Konten lama = Markdown → tetap dirender.
        $html = str_contains($konten, '<')
            ? $konten
            : Str::markdown($konten, ['html_input' => 'strip', 'allow_unsafe_links' => false]);

        return view('livewire.public.artikel-show', [
            'html' => $html,
        ]);
    }
}