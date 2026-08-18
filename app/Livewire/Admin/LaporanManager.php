<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\AlasanRouting;
use App\Enums\StatusLaporan;
use App\Livewire\Concerns\MemberiUmpanBalik;
use App\Models\Laporan;
use App\Models\LaporanKategori;
use App\Services\Laporan\LaporanService;
use App\Services\Wilayah\WilayahScopeService;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Moderasi laporan lintas wilayah.
 *
 * Query tetap melewati WilayahScopeService meski role platform tidak
 * dibatasi olehnya. Itu disengaja: kalau suatu saat admin diberi
 * cakupan wilayah, pembatasannya sudah terpasang di tempat yang benar
 * dan tidak perlu ditambahkan ulang di sini, persis kesalahan yang
 * dihindari dengan melarang filter wilayah manual.
 */
#[Title('Moderasi Laporan')]
class LaporanManager extends Component
{
    use MemberiUmpanBalik;
    use WithPagination;

    #[Url(as: 'status', except: '')]
    public string $status = '';

    #[Url(as: 'kategori', except: '')]
    public string $kategoriId = '';

    #[Url(as: 'routing', except: '')]
    public string $alasanRouting = '';

    #[Url(as: 'q', except: '')]
    public string $cari = '';

    public ?int $tolakId = null;

    public string $alasanTolak = '';

    public function updated(string $properti): void
    {
        if (! in_array($properti, ['page', 'alasanTolak', 'tolakId'], true)) {
            $this->resetPage();
        }
    }

    public function bukaFormTolak(int $id): void
    {
        $this->resetValidation();
        $this->tolakId = $id;
        $this->alasanTolak = '';
    }

    public function tolak(LaporanService $service): void
    {
        $laporan = Laporan::findOrFail($this->tolakId);

        $this->authorize('tolak', $laporan);

        $this->validate(
            ['alasanTolak' => ['required', 'string', 'min:10', 'max:500']],
            ['alasanTolak.required' => 'Alasan penolakan wajib diisi, pelapor membacanya.'],
        );

        $hasil = $this->jalankan(
            fn () => $service->tolak($laporan, auth()->user(), $this->alasanTolak),
            'Laporan ditolak dan pelapor sudah diberi tahu alasannya.',
        );

        if ($hasil !== null) {
            $this->reset(['tolakId', 'alasanTolak']);
        }
    }

    public function render(WilayahScopeService $scope)
    {
        $query = $scope
            ->applyLaporan(Laporan::query(), auth()->user())
            ->untukDaftar()
            ->latest('id');

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        if ($this->kategoriId !== '') {
            $query->where('kategori_id', (int) $this->kategoriId);
        }

        if ($this->alasanRouting !== '') {
            $query->where('alasan_routing', $this->alasanRouting);
        }

        if (trim($this->cari) !== '') {
            $kata = trim($this->cari);
            $query->where(fn ($q) => $q
                ->where('judul', 'like', "%{$kata}%")
                ->orWhere('tiket', 'like', "%{$kata}%")
                ->orWhere('alamat', 'like', "%{$kata}%"));
        }

        return view('livewire.admin.laporan-manager', [
            'daftar' => $query->paginate(20),
            'statusTersedia' => StatusLaporan::options(),
            'routingTersedia' => AlasanRouting::options(),
            'kategoriTersedia' => LaporanKategori::query()->orderBy('nama')->pluck('nama', 'id'),
        ]);
    }
}
