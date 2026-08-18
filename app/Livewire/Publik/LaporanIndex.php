<?php

declare(strict_types=1);

namespace App\Livewire\Publik;

use App\Enums\StatusLaporan;
use App\Models\Laporan;
use App\Models\LaporanKategori;
use App\Models\Wilayah;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Papan laporan warga yang terbuka untuk umum.
 *
 * Keterbukaan ini disengaja dan punya dua akibat yang sama-sama
 * diinginkan. Pelapor bisa melihat bahwa masalah serupa memang
 * ditangani, sehingga melapor terasa berguna. Dan penanganan yang mandek
 * jadi terlihat oleh siapa saja, termasuk oleh atasan pihak yang
 * seharusnya menanganinya.
 *
 * Yang tidak ikut terbuka: koordinat tepat dan identitas pelapor.
 * Transparansi menyangkut penanganan, bukan menyangkut siapa yang
 * melapor dari rumah mana.
 */
#[Layout('components.layouts.publik')]
#[Title('Laporan Warga')]
class LaporanIndex extends Component
{
    use WithPagination;

    #[Url(as: 'status', except: '')]
    public string $status = '';

    #[Url(as: 'kategori', except: '')]
    public string $kategoriId = '';

    #[Url(as: 'wilayah', except: '')]
    public string $wilayahId = '';

    #[Url(as: 'tiket', except: '')]
    public string $cari = '';

    public function updated(string $properti): void
    {
        if ($properti !== 'page') {
            $this->resetPage();
        }
    }

    public function render()
    {
        $query = Laporan::query()
            ->with(['kategori:id,nama,ikon', 'desa:id,nama', 'kabupaten:id,nama'])
            ->latest('id');

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        if ($this->kategoriId !== '') {
            $query->where('kategori_id', (int) $this->kategoriId);
        }

        if ($this->wilayahId !== '') {
            $query->where('kabupaten_id', (int) $this->wilayahId);
        }

        if (trim($this->cari) !== '') {
            $kata = trim($this->cari);
            $query->where(fn ($q) => $q
                ->where('tiket', 'like', "%{$kata}%")
                ->orWhere('judul', 'like', "%{$kata}%"));
        }

        return view('livewire.publik.laporan-index', [
            'daftar' => $query->paginate(12),
            'statusTersedia' => StatusLaporan::options(),
            'kategoriTersedia' => LaporanKategori::query()->aktif()->orderBy('nama')->pluck('nama', 'id'),

            // Hanya kabupaten yang benar-benar punya laporan. Memuat
            // seluruh kabupaten di Indonesia ke dalam satu dropdown
            // membuat penyaring ini lebih sulit dipakai daripada tidak ada.
            'wilayahTersedia' => Wilayah::query()
                ->whereIn('id', Laporan::query()->whereNotNull('kabupaten_id')->distinct()->pluck('kabupaten_id'))
                ->orderBy('nama')
                ->get()
                ->mapWithKeys(fn (Wilayah $w): array => [$w->id => $w->namaLengkap()]),
        ]);
    }
}
