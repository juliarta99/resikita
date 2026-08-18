<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\StatusArtikel;
use App\Enums\TipeArtikel;
use App\Livewire\Concerns\MemberiUmpanBalik;
use App\Models\Artikel;
use App\Models\ArtikelKategori;
use App\Services\Konten\TeksBacaService;
use App\Support\Unggahan;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Manajemen artikel literasi.
 *
 * `teks_baca` tidak pernah diisi manual. TeksBacaService membersihkan
 * markdown setiap kali artikel disimpan, sehingga pemutar suara di web
 * dan di aplikasi ponsel membacakan kalimat yang persis sama, keduanya
 * membaca kolom yang sama (CLAUDE.md 10.4).
 */
#[Title('Manajemen Artikel')]
class ArtikelManager extends Component
{
    use MemberiUmpanBalik;
    use WithFileUploads;
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $cari = '';

    #[Url(as: 'status', except: '')]
    public string $status = '';

    public bool $formTerbuka = false;

    public ?int $artikelId = null;

    public string $judul = '';

    public string $kategoriId = '';

    public string $tipe = 'artikel';

    public string $konten = '';

    public string $videoUrl = '';

    public string $statusArtikel = 'draft';

    public bool $isUnggulan = false;

    public $thumbnailBaru;

    public function updated(string $properti): void
    {
        if (in_array($properti, ['cari', 'status'], true)) {
            $this->resetPage();
        }
    }

    public function bukaForm(?int $id = null): void
    {
        $this->resetValidation();
        $this->reset([
            'artikelId', 'judul', 'kategoriId', 'tipe', 'konten',
            'videoUrl', 'statusArtikel', 'isUnggulan', 'thumbnailBaru',
        ]);

        if ($id !== null) {
            $artikel = Artikel::findOrFail($id);

            $this->artikelId = $artikel->id;
            $this->judul = $artikel->judul;
            $this->kategoriId = (string) ($artikel->kategori_id ?? '');
            $this->tipe = $artikel->tipe->value;
            $this->konten = $artikel->konten;
            $this->videoUrl = $artikel->video_url ?? '';
            $this->statusArtikel = $artikel->status->value;
            $this->isUnggulan = $artikel->is_unggulan;
        }

        $this->formTerbuka = true;
    }

    public function tutupForm(): void
    {
        $this->formTerbuka = false;
        $this->resetValidation();
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'judul' => ['required', 'string', 'min:5', 'max:191'],
            'kategoriId' => ['nullable', 'integer', Rule::exists('artikel_kategori', 'id')],
            'tipe' => ['required', Rule::enum(TipeArtikel::class)],
            'konten' => ['required', 'string', 'min:50'],
            'videoUrl' => ['nullable', 'url', 'max:255'],
            'statusArtikel' => ['required', Rule::enum(StatusArtikel::class)],
            'thumbnailBaru' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'kategoriId' => 'kategori', 'statusArtikel' => 'status',
            'videoUrl' => 'tautan video', 'thumbnailBaru' => 'gambar sampul',
        ];
    }

    public function simpan(TeksBacaService $teksBaca): void
    {
        $this->validate();

        $atribut = [
            'penulis_id' => auth()->id(),
            'kategori_id' => $this->kategoriId !== '' ? (int) $this->kategoriId : null,
            'tipe' => $this->tipe,
            'judul' => $this->judul,
            'konten' => $this->konten,
            'video_url' => $this->videoUrl ?: null,
            'status' => $this->statusArtikel,
            'is_unggulan' => $this->isUnggulan,
        ];

        if ($this->thumbnailBaru !== null) {
            $atribut['thumbnail'] = Unggahan::simpan($this->thumbnailBaru, 'artikel');
        }

        if ($this->artikelId === null) {
            $atribut['slug'] = $this->slugUnik($this->judul);
            $artikel = new Artikel($atribut);
        } else {
            $artikel = Artikel::findOrFail($this->artikelId);
            $artikel->fill($atribut);
        }

        // Waktu terbit disetel sekali, saat artikel pertama kali
        // dinaikkan dari draf. Menyetelnya ulang tiap penyuntingan akan
        // melemparkan artikel lama ke urutan teratas setiap kali salah
        // ketik diperbaiki.
        if ($this->statusArtikel === StatusArtikel::Published->value && $artikel->published_at === null) {
            $artikel->published_at = now();
        }

        $teksBaca->siapkan($artikel);
        $artikel->save();

        $this->pesanSukses($this->artikelId === null ? 'Artikel dibuat.' : 'Artikel diperbarui.');
        $this->tutupForm();
    }

    public function ubahStatus(int $id): void
    {
        $artikel = Artikel::findOrFail($id);

        $terbit = $artikel->status === StatusArtikel::Published;

        $artikel->update([
            'status' => $terbit ? StatusArtikel::Draft : StatusArtikel::Published,
            'published_at' => $terbit ? $artikel->published_at : ($artikel->published_at ?? now()),
        ]);

        $this->pesanSukses($terbit ? 'Artikel dikembalikan ke draf.' : 'Artikel diterbitkan.');
    }

    public function hapus(int $id): void
    {
        Artikel::findOrFail($id)->delete();

        $this->pesanSukses('Artikel dihapus.');
    }

    private function slugUnik(string $judul): string
    {
        $dasar = Str::slug($judul);
        $slug = $dasar;
        $urutan = 1;

        while (Artikel::query()->where('slug', $slug)->exists()) {
            $slug = $dasar.'-'.(++$urutan);
        }

        return $slug;
    }

    public function render()
    {
        $query = Artikel::query()->with(['kategori:id,nama', 'penulis:id,name'])->latest('id');

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        if (trim($this->cari) !== '') {
            $query->where('judul', 'like', '%'.trim($this->cari).'%');
        }

        return view('livewire.admin.artikel-manager', [
            'daftar' => $query->paginate(12),
            'kategoriTersedia' => ArtikelKategori::query()->orderBy('nama')->pluck('nama', 'id'),
            'tipeTersedia' => TipeArtikel::options(),
            'statusTersedia' => StatusArtikel::options(),
        ]);
    }
}
