<?php

declare(strict_types=1);

namespace App\Livewire\Fasilitator;

use App\Services\Laporan\TindakLanjutService;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Papan laporan dari wilayah yang belum terjangkau.
 *
 * Daftar ini tidak lewat WilayahScopeService, dan itu disengaja:
 * fasilitator justru bekerja pada laporan yang tidak punya wilayah
 * penanggung jawab. Pembatasannya bukan wilayah, melainkan alasan
 * routing, hanya laporan berlabel `wilayah_belum_terjangkau` yang
 * muncul di sini, dan itu ditegakkan TindakLanjutService.
 */
#[Title('Laporan Belum Terjangkau')]
class PapanLaporan extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $cari = '';

    /** Sembunyikan laporan yang sudah pernah diteruskan ke dinas. */
    #[Url(as: 'menganggur', except: false)]
    public bool $hanyaBelumDitindaklanjuti = false;

    #[Url(as: 'milikku', except: false)]
    public bool $hanyaMilikSaya = false;

    public function updated(string $properti): void
    {
        if ($properti !== 'page') {
            $this->resetPage();
        }
    }

    public function render(TindakLanjutService $tindakLanjut)
    {
        $query = $tindakLanjut
            ->papanLaporanBelumTerjangkau($this->hanyaMilikSaya ? auth()->user() : null);

        if ($this->hanyaBelumDitindaklanjuti) {
            $query->whereDoesntHave('tindakLanjut');
        }

        if (trim($this->cari) !== '') {
            $kata = trim($this->cari);
            $query->where(fn ($q) => $q
                ->where('judul', 'like', "%{$kata}%")
                ->orWhere('tiket', 'like', "%{$kata}%")
                ->orWhere('alamat', 'like', "%{$kata}%"));
        }

        return view('livewire.fasilitator.papan-laporan', [
            'daftar' => $query->paginate(15),
        ]);
    }
}
