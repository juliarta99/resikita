<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Livewire\Concerns\MemberiUmpanBalik;
use App\Models\ArtikelKategori;
use App\Models\LaporanKategori;
use App\Models\ProdukKategori;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Master data kategori: laporan, artikel, dan produk.
 *
 * Digabung dalam satu halaman bertab karena ketiganya adalah pekerjaan
 * yang sama, daftar pendek yang jarang berubah. Memberinya tiga menu
 * terpisah akan memenuhi bilah sisi dengan halaman yang dibuka beberapa
 * kali setahun.
 *
 * Kategori laporan tidak dihapus, hanya dinonaktifkan. Laporan lama
 * menunjuk kategorinya lewat foreign key; menghapusnya akan
 * meninggalkan riwayat warga tanpa keterangan apa yang dulu ia laporkan.
 */
#[Title('Master Data')]
class MasterData extends Component
{
    use MemberiUmpanBalik;

    #[Url(as: 'tab', except: 'laporan')]
    public string $tab = 'laporan';

    public bool $formTerbuka = false;

    public ?int $itemId = null;

    public string $nama = '';

    public string $deskripsi = '';

    public string $ikon = '';

    public bool $isActive = true;

    public function gantiTab(string $tab): void
    {
        $this->tab = in_array($tab, ['laporan', 'artikel', 'produk'], true) ? $tab : 'laporan';
        $this->tutupForm();
    }

    public function bukaForm(?int $id = null): void
    {
        $this->resetValidation();
        $this->reset(['itemId', 'nama', 'deskripsi', 'ikon', 'isActive']);

        if ($id !== null) {
            $item = $this->kelas()::findOrFail($id);

            $this->itemId = $item->id;
            $this->nama = $item->nama;
            $this->deskripsi = $item->deskripsi ?? '';
            $this->ikon = $item->ikon ?? '';
            $this->isActive = $item->is_active ?? true;
        }

        $this->formTerbuka = true;
    }

    public function tutupForm(): void
    {
        $this->formTerbuka = false;
        $this->resetValidation();
    }

    public function simpan(): void
    {
        $this->validate([
            'nama' => ['required', 'string', 'min:2', 'max:150'],
            'deskripsi' => ['nullable', 'string', 'max:500'],
            'ikon' => ['nullable', 'string', 'max:100'],
        ], attributes: ['nama' => 'nama kategori']);

        $kelas = $this->kelas();
        $atribut = ['nama' => $this->nama];

        if ($this->tab === 'laporan') {
            $atribut['deskripsi'] = $this->deskripsi ?: null;
            $atribut['ikon'] = $this->ikon ?: null;
            $atribut['is_active'] = $this->isActive;
        } else {
            $atribut['slug'] = $this->slugUnik($kelas, $this->nama, $this->itemId);

            if ($this->tab === 'produk') {
                $atribut['ikon'] = $this->ikon ?: null;
            }
        }

        if ($this->itemId === null) {
            $kelas::create($atribut);
            $this->pesanSukses('Kategori ditambahkan.');
        } else {
            $kelas::findOrFail($this->itemId)->update($atribut);
            $this->pesanSukses('Kategori diperbarui.');
        }

        $this->tutupForm();
    }

    public function ubahAktif(int $id): void
    {
        if ($this->tab !== 'laporan') {
            return;
        }

        $item = LaporanKategori::findOrFail($id);
        $item->update(['is_active' => ! $item->is_active]);

        $this->pesanSukses($item->is_active
            ? 'Kategori kembali bisa dipilih warga.'
            : 'Kategori disembunyikan. Laporan lama tetap menunjukkan kategorinya.');
    }

    /** @return class-string<Model> */
    private function kelas(): string
    {
        return match ($this->tab) {
            'artikel' => ArtikelKategori::class,
            'produk' => ProdukKategori::class,
            default => LaporanKategori::class,
        };
    }

    /** @param class-string<Model> $kelas */
    private function slugUnik(string $kelas, string $nama, ?int $abaikanId): string
    {
        $dasar = Str::slug($nama);
        $slug = $dasar;
        $urutan = 1;

        while ($kelas::query()
            ->where('slug', $slug)
            ->when($abaikanId !== null, fn ($q) => $q->whereKeyNot($abaikanId))
            ->exists()
        ) {
            $slug = $dasar.'-'.(++$urutan);
        }

        return $slug;
    }

    public function render()
    {
        $daftar = match ($this->tab) {
            'artikel' => ArtikelKategori::query()->withCount('artikel')->orderBy('nama')->get(),
            'produk' => ProdukKategori::query()->withCount('produk')->orderBy('nama')->get(),
            default => LaporanKategori::query()->withCount('laporan')->orderBy('nama')->get(),
        };

        return view('livewire.admin.master-data', ['daftar' => $daftar]);
    }
}
