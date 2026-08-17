<?php

declare(strict_types=1);

namespace App\Livewire\Pemerintahan;

use App\Enums\StatusLaporan;
use App\Models\Laporan;
use App\Models\LaporanKategori;
use App\Services\Analitik\EksporService;
use App\Services\Wilayah\WilayahScopeService;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Daftar laporan dalam cakupan kewenangan pengguna.
 *
 * Seluruh pembatasan wilayah datang dari WilayahScopeService. Penyaring
 * di halaman ini, status, kategori, kata kunci, hanya mempersempit
 * apa yang sudah boleh dilihat; tidak satu pun di antaranya menentukan
 * siapa boleh melihat apa.
 */
#[Title('Manajemen Laporan')]
class LaporanManager extends Component
{
    use WithPagination;

    #[Url(as: 'status', except: '')]
    public string $status = '';

    #[Url(as: 'kategori', except: '')]
    public string $kategoriId = '';

    #[Url(as: 'q', except: '')]
    public string $cari = '';

    /** Hanya laporan yang menunggu tindakan penanggung jawabnya. */
    #[Url(as: 'antrean', except: false)]
    public bool $hanyaPerluTindakan = false;

    public function updated(string $properti): void
    {
        // Setiap perubahan penyaring mengembalikan pembacaan ke halaman
        // pertama. Tanpa ini, penyaring baru yang hanya menghasilkan dua
        // baris akan tampil kosong karena pembaca masih di halaman tiga.
        if ($properti !== 'page') {
            $this->resetPage();
        }
    }

    public function bersihkanFilter(): void
    {
        $this->reset(['status', 'kategoriId', 'cari', 'hanyaPerluTindakan']);
        $this->resetPage();
    }

    /**
     * Unduh rekap laporan yang sedang ditampilkan.
     *
     * Penyaring layar ikut diserahkan ke EksporService supaya berkasnya
     * berisi persis apa yang sedang dilihat. Tombol unduh yang diam-diam
     * mengambil seluruh tabel adalah cara paling mudah membocorkan data
     * lintas daerah tanpa meninggalkan jejak, berkasnya berpindah
     * tangan lewat surel, jauh dari sistem.
     */
    public function ekspor(EksporService $ekspor)
    {
        return $ekspor->laporan(auth()->user(), function (Builder $query): void {
            $this->terapkanPenyaring($query);
        });
    }

    /** @param Builder<Laporan> $query */
    private function terapkanPenyaring(Builder $query): void
    {
        if ($this->hanyaPerluTindakan) {
            $query->menungguTindakan();
        } elseif ($this->status !== '') {
            $query->where('status', $this->status);
        }

        if ($this->kategoriId !== '') {
            $query->where('kategori_id', (int) $this->kategoriId);
        }

        if (trim($this->cari) !== '') {
            $kata = trim($this->cari);
            $query->where(fn ($q) => $q
                ->where('judul', 'like', "%{$kata}%")
                ->orWhere('tiket', 'like', "%{$kata}%")
                ->orWhere('alamat', 'like', "%{$kata}%"));
        }
    }

    public function render(WilayahScopeService $scope)
    {
        $query = $scope
            ->applyLaporan(Laporan::query(), auth()->user())
            ->untukDaftar()
            ->withCount('penugasan')
            ->latest('id');

        // Penyaringnya satu method, dipakai tampilan dan ekspor. Kalau
        // ditulis dua kali, berkas unduhan perlahan berisi hal yang
        // berbeda dari yang tampil di layar.
        $this->terapkanPenyaring($query);

        return view('livewire.pemerintahan.laporan-manager', [
            'daftar' => $query->paginate(15),
            'kategoriTersedia' => LaporanKategori::query()->aktif()->orderBy('nama')->pluck('nama', 'id'),
            'statusTersedia' => StatusLaporan::options(),
        ]);
    }
}
