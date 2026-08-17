<?php

declare(strict_types=1);

namespace App\Livewire\Publik;

use App\Models\BankSampah;
use App\Models\Tps;
use App\Models\Umkm;
use App\Services\Analitik\StatistikPublikService;
use App\Support\Haversine;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Direktori fasilitas: bank sampah, TPS, dan UMKM.
 *
 * Terbuka tanpa akun, dan itu keputusan sadar. Warga harus bisa
 * menemukan bank sampah terdekat sebelum memutuskan mendaftar,
 * mewajibkan akun lebih dulu membalik urutan yang masuk akal dan
 * membuang orang yang baru sekadar penasaran.
 *
 * Titik laporan tidak ikut di peta ini. Koordinat laporan menunjuk
 * tempat yang bisa jadi halaman rumah seseorang, dan menyebarnya di
 * peta terbuka mengubah alat pelaporan menjadi alat menunjuk tetangga.
 */
#[Layout('components.layouts.publik')]
#[Title('Peta & Direktori')]
class Direktori extends Component
{
    use WithPagination;

    #[Url(as: 'jenis', except: 'bank_sampah')]
    public string $jenis = 'bank_sampah';

    #[Url(as: 'q', except: '')]
    public string $cari = '';

    /** Titik acuan pengguna, diisi tombol "gunakan lokasi saya". */
    public ?float $latitude = null;

    public ?float $longitude = null;

    public function updated(string $properti): void
    {
        if (! in_array($properti, ['page', 'latitude', 'longitude'], true)) {
            $this->resetPage();
        }
    }

    /** Dipanggil peramban setelah pengguna mengizinkan akses lokasi. */
    public function pakaiLokasi(float $latitude, float $longitude): void
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->resetPage();
    }

    public function lupakanLokasi(): void
    {
        $this->latitude = null;
        $this->longitude = null;
        $this->resetPage();
    }

    public function render(StatistikPublikService $statistik)
    {
        $query = match ($this->jenis) {
            'tps' => Tps::query()->with('wilayah:id,nama,tingkat')->withCount('anggota'),
            'umkm' => Umkm::query()
                ->aktif()
                ->with('wilayah:id,nama,tingkat')
                ->withCount('produk')
                ->withAvg('ulasan as rating_rata', 'rating'),
            default => BankSampah::query()
                ->aktif()
                ->with('wilayah:id,nama,tingkat')
                ->withCount('harga'),
        };

        if (trim($this->cari) !== '') {
            $kata = trim($this->cari);
            $query->where(fn ($q) => $q
                ->where('nama', 'like', "%{$kata}%")
                ->orWhere('alamat', 'like', "%{$kata}%"));
        }

        // Urut terdekat kalau pengguna berbagi lokasinya. Jaraknya
        // dihitung di basis data, bukan setelah semua baris ditarik.
        if ($this->latitude !== null && $this->longitude !== null) {
            Haversine::terapkan($query, $this->latitude, $this->longitude);
        } else {
            $query->orderBy('nama');
        }

        return view('livewire.publik.direktori', [
            'daftar' => $query->paginate(12),
            'titik' => $statistik->titikFasilitas(),
        ]);
    }
}
