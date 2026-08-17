<?php

declare(strict_types=1);

namespace App\Livewire\Umkm;

use App\Enums\StatusPesanan;
use App\Livewire\Concerns\MemberiUmpanBalik;
use App\Models\Pesanan;
use App\Services\Marketplace\PesananService;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Pesanan masuk untuk satu UMKM.
 *
 * Perpindahan status seluruhnya lewat PesananService, yang menegakkan
 * transisi sah dari StatusPesanan dan mengkredit saldo penjual pada
 * saat yang tepat. Komponen ini tidak pernah menyentuh kolom `status`
 * secara langsung, kalau itu terjadi, pesanan bisa melompat dari
 * "menunggu bayar" ke "selesai" tanpa uang pernah berpindah.
 */
#[Title('Pesanan')]
class PesananManager extends Component
{
    use MemberiUmpanBalik;
    use WithPagination;

    #[Url(as: 'status', except: '')]
    public string $status = '';

    #[Url(as: 'q', except: '')]
    public string $cari = '';

    public ?int $resiUntuk = null;

    public string $noResi = '';

    public string $kurirDipakai = '';

    public function updated(string $properti): void
    {
        if (! in_array($properti, ['page', 'noResi', 'resiUntuk'], true)) {
            $this->resetPage();
        }
    }

    public function tandaiDikemas(int $id, PesananService $service): void
    {
        $pesanan = $this->milikToko()->findOrFail($id);

        $this->jalankan(
            fn () => $service->tandaiDikemas($pesanan),
            'Pesanan ditandai sedang dikemas. Pembeli menerima pemberitahuan.',
        );
    }

    public function bukaFormResi(int $id): void
    {
        $pesanan = $this->milikToko()->findOrFail($id);

        $this->resetValidation();
        $this->resiUntuk = $pesanan->id;
        $this->noResi = $pesanan->no_resi ?? '';
        $this->kurirDipakai = trim(($pesanan->kurir ?? '').' '.($pesanan->layanan_kurir ?? ''));
    }

    public function kirim(PesananService $service): void
    {
        $this->validate(
            ['noResi' => ['required', 'string', 'min:5', 'max:100']],
            [
                'noResi.required' => 'Nomor resi wajib diisi.',
                'noResi.min' => 'Nomor resi terlalu pendek untuk bisa dilacak.',
            ],
        );

        $pesanan = $this->milikToko()->findOrFail($this->resiUntuk);

        $hasil = $this->jalankan(
            fn () => $service->tandaiDikirim($pesanan, trim($this->noResi)),
            'Nomor resi tersimpan. Pembeli bisa melacak paketnya sekarang.',
        );

        if ($hasil !== null) {
            $this->reset(['resiUntuk', 'noResi', 'kurirDipakai']);
        }
    }

    /** @return Builder<Pesanan> */
    private function milikToko()
    {
        return Pesanan::query()->where('umkm_id', auth()->user()->umkm_id);
    }

    public function render()
    {
        if (auth()->user()->umkm_id === null) {
            return view('livewire.umkm.pesanan-manager', ['daftar' => null]);
        }

        $query = $this->milikToko()
            ->with(['user:id,name', 'item.produk:id,nama,slug'])
            ->latest('id');

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        if (trim($this->cari) !== '') {
            $kata = trim($this->cari);
            $query->where(fn ($q) => $q
                ->where('kode', 'like', "%{$kata}%")
                ->orWhere('nama_penerima', 'like', "%{$kata}%")
                ->orWhere('no_resi', 'like', "%{$kata}%"));
        }

        return view('livewire.umkm.pesanan-manager', [
            'daftar' => $query->paginate(12),
            'statusTersedia' => StatusPesanan::options(),
        ]);
    }
}
