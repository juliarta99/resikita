<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\StatusUmkm;
use App\Livewire\Concerns\MemberiUmpanBalik;
use App\Models\Umkm;
use App\Services\Auth\AkunService;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Verifikasi UMKM sebelum boleh berjualan.
 *
 * Marketplace yang bisa diisi penjual mana pun tanpa peninjauan adalah
 * masalah perlindungan konsumen, bukan sekadar kualitas data. Sampai
 * disetujui, toko berstatus menunggu dan panel penjualnya tertutup,
 * yang tertutup tokonya, bukan akun pemiliknya.
 *
 * Penolakan wajib membawa alasan, dan alasan itu dibaca langsung oleh
 * pemilik usaha di halaman status pendaftarannya. Karena itu tulisannya
 * ditujukan kepadanya, bukan catatan internal antar-admin.
 */
#[Title('Manajemen UMKM')]
class UmkmManager extends Component
{
    use MemberiUmpanBalik;
    use WithPagination;

    #[Url(as: 'status', except: 'menunggu')]
    public string $status = 'menunggu';

    #[Url(as: 'q', except: '')]
    public string $cari = '';

    public ?int $tolakId = null;

    public string $catatanTolak = '';

    public function updated(string $properti): void
    {
        if (! in_array($properti, ['page', 'tolakId', 'catatanTolak'], true)) {
            $this->resetPage();
        }
    }

    public function setujui(int $id, AkunService $akun): void
    {
        $umkm = Umkm::findOrFail($id);

        $this->jalankan(
            fn () => $akun->setujuiUmkm($umkm, auth()->user()),
            "{$umkm->nama} disetujui. Produknya kini bisa tampil di marketplace.",
        );
    }

    public function bukaFormTolak(int $id): void
    {
        $this->resetValidation();
        $this->tolakId = $id;
        $this->catatanTolak = '';
    }

    public function batalTolak(): void
    {
        $this->reset(['tolakId', 'catatanTolak']);
        $this->resetValidation();
    }

    public function tolak(AkunService $akun): void
    {
        $umkm = Umkm::findOrFail($this->tolakId);

        $this->validate(
            ['catatanTolak' => ['required', 'string', 'min:10', 'max:1000']],
            [
                'catatanTolak.required' => 'Alasan penolakan wajib diisi.',
                'catatanTolak.min' => 'Jelaskan alasannya minimal 10 karakter, pemilik usaha membacanya.',
            ],
        );

        $hasil = $this->jalankan(
            fn () => $akun->tolakUmkm($umkm, auth()->user(), $this->catatanTolak),
            "{$umkm->nama} ditolak. Pemiliknya bisa membaca alasannya dan mengajukan ulang.",
        );

        if ($hasil !== null) {
            $this->reset(['tolakId', 'catatanTolak']);
        }
    }

    public function render()
    {
        $query = Umkm::query()
            ->with(['wilayah:id,nama,tingkat', 'peninjau:id,name'])
            ->withCount('produk')
            ->latest('id');

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        if (trim($this->cari) !== '') {
            $kata = trim($this->cari);
            $query->where(fn ($q) => $q
                ->where('nama', 'like', "%{$kata}%")
                ->orWhere('email', 'like', "%{$kata}%"));
        }

        return view('livewire.admin.umkm-manager', [
            'daftar' => $query->paginate(12),
            'statusTersedia' => StatusUmkm::options(),
            'jumlahMenunggu' => Umkm::query()->where('status', StatusUmkm::Menunggu)->count(),
        ]);
    }
}
