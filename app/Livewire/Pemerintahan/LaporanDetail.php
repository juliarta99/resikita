<?php

declare(strict_types=1);

namespace App\Livewire\Pemerintahan;

use App\Enums\Role;
use App\Livewire\Concerns\MemberiUmpanBalik;
use App\Models\Laporan;
use App\Models\User;
use App\Services\Laporan\LaporanService;
use App\Services\Wilayah\WilayahScopeService;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Peninjauan satu laporan: verifikasi, penolakan, dan penugasan.
 *
 * Ketiga tindakan itu mengubah status, mencatat riwayat, dan mengirim
 * notifikasi. Semuanya di LaporanService, komponen ini hanya
 * menyerahkan masukan dan menyampaikan hasilnya, sehingga jalur web dan
 * endpoint mobile mengubah laporan dengan aturan yang sama.
 *
 * Otorisasi tiap tindakan lewat LaporanPolicy, yang memeriksa dua hal
 * sekaligus: permission role dan cakupan wilayah. Memeriksa permission
 * saja akan mengizinkan admin kabupaten menyentuh laporan kabupaten
 * tetangga.
 */
#[Title('Tinjau Laporan')]
class LaporanDetail extends Component
{
    use MemberiUmpanBalik;

    public Laporan $laporan;

    public bool $formTolakTerbuka = false;

    public string $alasanTolak = '';

    public bool $formTugasTerbuka = false;

    public string $petugasId = '';

    public string $catatanTugas = '';

    public function mount(Laporan $laporan): void
    {
        $this->authorize('view', $laporan);

        $this->laporan = $laporan;
    }

    // ----------------------------------------------------------------
    // Tindakan
    // ----------------------------------------------------------------

    public function verifikasi(LaporanService $service): void
    {
        $this->authorize('verifikasi', $this->laporan);

        $hasil = $this->jalankan(
            fn () => $service->verifikasi($this->laporan, auth()->user()),
            'Laporan diverifikasi. Selanjutnya tugaskan petugas untuk menanganinya.',
        );

        if ($hasil !== null) {
            $this->laporan = $hasil->fresh();
        }
    }

    public function tolak(LaporanService $service): void
    {
        $this->authorize('tolak', $this->laporan);

        $this->validate(
            ['alasanTolak' => ['required', 'string', 'min:10', 'max:500']],
            [
                'alasanTolak.required' => 'Alasan penolakan wajib diisi.',
                'alasanTolak.min' => 'Jelaskan alasannya minimal 10 karakter, pelapor membacanya.',
            ],
        );

        $hasil = $this->jalankan(
            fn () => $service->tolak($this->laporan, auth()->user(), $this->alasanTolak),
            'Laporan ditolak dan pelapor sudah diberi tahu alasannya.',
        );

        if ($hasil !== null) {
            $this->laporan = $hasil->fresh();
            $this->reset(['formTolakTerbuka', 'alasanTolak']);
        }
    }

    public function tugaskan(LaporanService $service): void
    {
        $this->authorize('tugaskan', $this->laporan);

        $this->validate(
            [
                'petugasId' => ['required', 'integer'],
                'catatanTugas' => ['nullable', 'string', 'max:500'],
            ],
            ['petugasId.required' => 'Pilih petugas yang akan menangani laporan ini.'],
        );

        $petugas = $this->petugasTersedia()->firstWhere('id', (int) $this->petugasId);

        if ($petugas === null) {
            $this->pesanGalat('Petugas itu tidak ada dalam cakupan kewenangan Anda.');

            return;
        }

        $hasil = $this->jalankan(
            fn () => $service->tugaskan($this->laporan, $petugas, auth()->user(), $this->catatanTugas ?: null),
            "Laporan ditugaskan kepada {$petugas->name}.",
        );

        if ($hasil !== null) {
            $this->laporan = $this->laporan->fresh();
            $this->reset(['formTugasTerbuka', 'petugasId', 'catatanTugas']);
        }
    }

    // ----------------------------------------------------------------
    // Data pendukung
    // ----------------------------------------------------------------

    /**
     * Petugas yang boleh ditugaskan pengguna ini.
     *
     * Dibatasi WilayahScopeService, bukan disaring manual: daftar
     * petugas adalah tempat kebocoran kewenangan yang paling tidak
     * kentara, menugaskan petugas kabupaten lain terlihat seperti
     * kesalahan biasa, padahal itu tindakan di luar wewenang.
     *
     * @return Collection<int, User>
     */
    private function petugasTersedia()
    {
        return app(WilayahScopeService::class)
            ->applyPengguna(User::query()->denganRole(Role::Petugas)->aktif(), auth()->user())
            ->orderBy('name')
            ->get();
    }

    public function render()
    {
        $this->laporan->loadMissing([
            'kategori', 'pelapor', 'foto', 'desa', 'kecamatan', 'kabupaten', 'provinsi',
            'penanggungJawab', 'verifikator',
            'penugasan.petugas', 'progres.petugas',
        ]);

        return view('livewire.pemerintahan.laporan-detail', [
            'petugasPilihan' => $this->formTugasTerbuka ? $this->petugasTersedia() : collect(),
        ]);
    }
}
