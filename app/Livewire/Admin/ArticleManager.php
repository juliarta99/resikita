<?php

namespace App\Livewire\Admin;

use App\Models\Article;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class ArticleManager extends Component
{
    use WithFileUploads;
    use WithPagination;

    public bool $showForm = false;
    public bool $showDelete = false;
    public ?int $deleteId = null;

    public ?int $editingId = null;
    public string $judul = '';
    public string $tipe = 'artikel';
    public string $konten = '';
    public string $status = 'draft';

    public $thumbnail;
    public ?string $thumbnailLama = null;

    public array $tipeList = ['artikel' => 'Artikel', 'panduan' => 'Panduan', 'tutorial' => 'Tutorial', 'jurnal' => 'Jurnal'];

    protected function rules(): array
    {
        return [
            'judul'     => 'required|string|max:255',
            'tipe'      => 'required|in:artikel,panduan,tutorial,jurnal',
            'konten'    => 'required|string',
            'status'    => 'required|in:draft,published',
            'thumbnail' => 'nullable|image|max:2048',
        ];
    }

    public function tambah()
    {
        $this->batal();
        $this->showForm = true;
        $this->dispatch('editor-content', konten: '');
    }

    public function edit(int $id)
    {
        $a = Article::findOrFail($id);
        $this->editingId = $a->id;
        $this->judul = $a->judul;
        $this->tipe = $a->tipe;
        $this->konten = $a->konten;
        $this->status = $a->status;
        $this->thumbnailLama = $a->thumbnail;
        $this->thumbnail = null;
        $this->showForm = true;
        $this->dispatch('editor-content', konten: $a->konten);
    }

    private function uniqueSlug(string $judul): string
    {
        $base = Str::slug($judul) ?: 'artikel';
        $slug = $base;
        $i = 1;
        while (Article::where('slug', $slug)->where('id', '!=', $this->editingId ?? 0)->exists()) {
            $slug = $base . '-' . (++$i);
        }
        return $slug;
    }

    public function simpan()
    {
        $data = $this->validate();

        if (mb_strlen(trim(strip_tags($this->konten))) < 10) {
            $this->addError('konten', 'Konten terlalu pendek atau kosong.');
            return;
        }

        $attrs = [
            'tipe'   => $data['tipe'],
            'judul'  => $data['judul'],
            'konten' => $data['konten'],
            'status' => $data['status'],
        ];

        if ($this->thumbnail) {
            if ($this->editingId && $this->thumbnailLama) {
                Storage::disk('public')->delete($this->thumbnailLama);
            }
            $attrs['thumbnail'] = $this->thumbnail->store('articles', 'public');
        }

        if ($this->editingId) {
            $article = Article::findOrFail($this->editingId);
            if ($data['status'] === 'published' && ! $article->published_at) {
                $attrs['published_at'] = now();
            }
            $article->update($attrs);
            $pesan = 'Artikel diperbarui.';
        } else {
            $attrs['author_id'] = Auth::id();
            $attrs['slug'] = $this->uniqueSlug($data['judul']);
            $attrs['published_at'] = $data['status'] === 'published' ? now() : null;
            Article::create($attrs);
            $pesan = 'Artikel dibuat.';
        }

        $this->batal();
        session()->flash('ok', $pesan);
    }

    public function konfirmHapus(int $id)
    {
        $this->deleteId = $id;
        $this->showDelete = true;
    }

    public function hapus()
    {
        $this->showDelete = false;
        $a = Article::find($this->deleteId);
        $this->deleteId = null;
        if ($a) {
            if ($a->thumbnail) {
                Storage::disk('public')->delete($a->thumbnail);
            }
            $a->delete();
            session()->flash('ok', 'Artikel dihapus.');
        }
    }

    public function batal()
    {
        $this->reset('showForm', 'editingId', 'judul', 'tipe', 'konten', 'status', 'thumbnail', 'thumbnailLama');
        $this->tipe = 'artikel';
        $this->status = 'draft';
    }

    public function render()
    {
        return view('livewire.admin.article-manager', [
            'daftar' => Article::with('author')->latest()->paginate(10),
        ]);
    }
}