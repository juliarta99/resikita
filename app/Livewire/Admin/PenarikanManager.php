<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\StatusPenarikan;
use App\Livewire\Concerns\MemberiUmpanBalik;
use App\Models\PenarikanSaldo;
use App\Models\UmkmPenarikan;
use App\Services\Wallet\PenarikanService;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Persetujuan penarikan saldo, warga maupun UMKM.
 *
 * Digabung dalam satu halaman karena keputusannya identik: memeriksa
 * rekening lalu menyetujui atau menolak. Memisahkannya menjadi dua menu
 * hanya membuat admin harus memeriksa dua tempat untuk pekerjaan yang
 * sama, dan salah satunya cepat atau lambat terlupakan.
 *
 * Saldo sudah dipotong sejak pengajuan dibuat. Penolakan karena itu
 * mengembalikannya, dan pengembalian itu dilakukan PenarikanService,
 * bukan di sini.
 */
#[Title('Penarikan Saldo')]
class PenarikanManager extends Component
{
    use MemberiUmpanBalik;
    use WithPagination;

    #[Url(as: 'jenis', except: 'warga')]
    public string $jenis = 'warga';

    #[Url(as: 'status', except: 'menunggu')]
    public string $status = 'menunggu';

    public ?int $tolakId = null;

    public string $alasanTolak = '';

    public function updated(string $properti): void
    {
        if (in_array($properti, ['jenis', 'status'], true)) {
            $this->reset(['tolakId', 'alasanTolak']);
            $this->resetPage();
        }
    }

    public function setujui(int $id, PenarikanService $service): void
    {
        $this->jalankan(function () use ($id, $service): void {
            if ($this->jenis === 'umkm') {
                $service->setujuiUmkm(UmkmPenarikan::findOrFail($id), auth()->user());
            } else {
                $service->setujui(PenarikanSaldo::findOrFail($id), auth()->user());
            }
        }, 'Penarikan disetujui. Tandai selesai setelah dana benar-benar ditransfer.');
    }

    public function tandaiSelesai(int $id, PenarikanService $service): void
    {
        if ($this->jenis === 'umkm') {
            // Alur UMKM berhenti di "disetujui"; tidak ada langkah
            // terpisah untuk menandai transfer selesai.
            $this->pesanGalat('Penarikan UMKM tidak punya langkah "selesai" terpisah.');

            return;
        }

        $this->jalankan(
            fn () => $service->tandaiSelesai(PenarikanSaldo::findOrFail($id)),
            'Penarikan ditandai selesai.',
        );
    }

    public function bukaFormTolak(int $id): void
    {
        $this->resetValidation();
        $this->tolakId = $id;
        $this->alasanTolak = '';
    }

    public function tolak(PenarikanService $service): void
    {
        $this->validate(
            ['alasanTolak' => ['required', 'string', 'min:10', 'max:500']],
            [
                'alasanTolak.required' => 'Alasan penolakan wajib diisi.',
                'alasanTolak.min' => 'Jelaskan alasannya minimal 10 karakter, pemohon membacanya.',
            ],
        );

        $hasil = $this->jalankan(function () use ($service): bool {
            if ($this->jenis === 'umkm') {
                $service->tolakUmkm(UmkmPenarikan::findOrFail($this->tolakId), auth()->user(), $this->alasanTolak);
            } else {
                $service->tolak(PenarikanSaldo::findOrFail($this->tolakId), auth()->user(), $this->alasanTolak);
            }

            return true;
        }, 'Penarikan ditolak dan saldonya sudah dikembalikan.');

        if ($hasil !== null) {
            $this->reset(['tolakId', 'alasanTolak']);
        }
    }

    public function render()
    {
        $query = $this->jenis === 'umkm'
            ? UmkmPenarikan::query()->with(['umkm:id,nama', 'penyetuju:id,name'])
            : PenarikanSaldo::query()->with(['user:id,name,email', 'penyetuju:id,name']);

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        return view('livewire.admin.penarikan-manager', [
            'daftar' => $query->latest('id')->paginate(12),
            'statusTersedia' => StatusPenarikan::options(),
            'menungguWarga' => PenarikanSaldo::query()->where('status', StatusPenarikan::Menunggu)->count(),
            'menungguUmkm' => UmkmPenarikan::query()->where('status', StatusPenarikan::Menunggu)->count(),
        ]);
    }
}
