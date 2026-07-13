<?php

namespace App\Livewire\Admin;

use App\Models\Article;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app')] // sesuaikan dengan layout admin Anda bila berbeda
class ArticleManager extends Component
{
    use WithFileUploads, WithPagination;

    // Filter
    public string $search = '';
    public string $filterTipe = '';
    public string $filterStatus = '';

    // Form
    public bool $showForm = false;
    public ?int $articleId = null;

    public string $judul = '';
    public string $slug = '';
    public string $tipe = 'artikel';
    public string $status = 'draft';
    public bool $is_unggulan = false;
    public ?string $video_url = null;
    public ?string $published_at = null;
    public string $konten = '';
    public $thumbnail = null;               // file baru (upload)
    public ?string $existingThumbnail = null;

    protected function rules(): array
    {
        return [
            'judul'        => 'required|string|max:255',
            'slug'         => 'required|string|max:255|unique:articles,slug,' . ($this->articleId ?? 'NULL') . ',id',
            'tipe'         => 'required|in:artikel,panduan,tutorial,jurnal',
            'status'       => 'required|in:draft,published',
            'is_unggulan'  => 'boolean',
            'video_url'    => 'nullable|url|max:255',
            'published_at' => 'nullable|date',
            'konten'       => 'required|string',
            'thumbnail'    => 'nullable|image|max:4096',
        ];
    }

    protected array $messages = [
        'konten.required' => 'Konten artikel wajib diisi.',
        'video_url.url'   => 'URL video tidak valid.',
    ];

    public function updatedJudul($value): void
    {
        if (! $this->articleId) {
            $this->slug = Str::slug($value);
        }
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFilterTipe(): void { $this->resetPage(); }
    public function updatingFilterStatus(): void { $this->resetPage(); }

    public function create(): void
    {
        $this->resetForm();
        $this->tipe = 'artikel';
        $this->status = 'draft';
        $this->published_at = now()->format('Y-m-d');
        $this->showForm = true;
        $this->dispatch('editor-set', html: '');
    }

    public function edit(int $id): void
    {
        $a = Article::findOrFail($id);
        $this->articleId        = $a->id;
        $this->judul            = $a->judul;
        $this->slug             = $a->slug;
        $this->tipe             = $a->tipe;
        $this->status           = $a->status;
        $this->is_unggulan      = (bool) $a->is_unggulan;
        $this->video_url        = $a->video_url;
        $this->published_at     = $a->published_at?->format('Y-m-d');
        $this->konten           = $a->konten ?? '';
        $this->existingThumbnail = $a->thumbnail;
        $this->thumbnail        = null;
        $this->showForm         = true;
        $this->dispatch('editor-set', html: $this->konten);
    }

    public function save(): void
    {
        $data = $this->validate();

        $payload = [
            'judul'        => $data['judul'],
            'slug'         => $data['slug'],
            'tipe'         => $data['tipe'],
            'status'       => $data['status'],
            'is_unggulan'  => $this->is_unggulan,
            'video_url'    => $data['video_url'] ?: null,
            'published_at' => $data['published_at'] ?: null,
            'konten'       => $data['konten'],
        ];

        if ($this->thumbnail) {
            $payload['thumbnail'] = $this->thumbnail->store('articles', 'public');
        }

        if ($this->articleId) {
            Article::findOrFail($this->articleId)->update($payload);
            $msg = 'Artikel berhasil diperbarui.';
        } else {
            $payload['author_id'] = Auth::id();
            Article::create($payload);
            $msg = 'Artikel berhasil dibuat.';
        }

        $this->showForm = false;
        $this->resetForm();
        session()->flash('ok', $msg);
    }

    public function delete(int $id): void
    {
        Article::findOrFail($id)->delete();
        session()->flash('ok', 'Artikel dihapus.');
    }

    public function toggleUnggulan(int $id): void
    {
        $a = Article::findOrFail($id);
        $a->update(['is_unggulan' => ! $a->is_unggulan]);
    }

    public function resetForm(): void
    {
        $this->reset([
            'articleId', 'judul', 'slug', 'tipe', 'status', 'is_unggulan',
            'video_url', 'published_at', 'konten', 'thumbnail', 'existingThumbnail',
        ]);
        $this->resetErrorBag();
    }

    public function render()
    {
        $articles = Article::with('author')
            ->when($this->search, fn ($q) => $q->where('judul', 'like', "%{$this->search}%"))
            ->when($this->filterTipe, fn ($q) => $q->where('tipe', $this->filterTipe))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->latest()
            ->paginate(10);

        return view('livewire.admin.article-manager', compact('articles'));
    }
}