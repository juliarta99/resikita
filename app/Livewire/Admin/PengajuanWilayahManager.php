<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\StatusPengajuanWilayah;
use App\Livewire\Concerns\MemberiUmpanBalik;
use App\Models\PengajuanWilayah;
use App\Services\Wilayah\PengajuanWilayahService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Verifikasi pengajuan pendaftaran wilayah.
 *
 * Ini keputusan paling berat di seluruh panel admin: menyetujui berarti
 * menyerahkan kewenangan atas seluruh laporan sebuah daerah kepada
 * seseorang. Karena itu surat bukti kewenangan disimpan di disk privat
 * dan hanya bisa diunduh lewat route bertoken, bukan lewat URL publik
 * yang bisa ditebak.
 *
 * Seluruh akibat penyetujuan, mengubah status wilayah, menerbitkan
 * akun pemerintah, menol-kan skor prioritas, ada di
 * PengajuanWilayahService dalam satu transaksi.
 */
#[Title('Pengajuan Wilayah')]
class PengajuanWilayahManager extends Component
{
    use MemberiUmpanBalik;
    use WithPagination;

    #[Url(as: 'status', except: 'diajukan')]
    public string $status = 'diajukan';

    public ?int $tolakId = null;

    public string $catatanTolak = '';

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function setujui(int $id, PengajuanWilayahService $service): void
    {
        $pengajuan = PengajuanWilayah::findOrFail($id);

        $this->authorize('tinjau', $pengajuan);

        $hasil = $this->jalankan(
            fn () => $service->setujui($pengajuan, auth()->user()),
            'Pengajuan disetujui. Wilayah kini terverifikasi dan akun pemerintahnya sudah diterbitkan.',
        );

        if ($hasil !== null) {
            $this->pesanSukses(
                'Pengajuan disetujui. Beri tahu pemohon untuk memakai menu "Lupa kata sandi" '
                .'agar bisa menyetel kata sandinya sendiri.',
            );
        }
    }

    public function bukaFormTolak(int $id): void
    {
        $this->resetValidation();
        $this->tolakId = $id;
        $this->catatanTolak = '';
    }

    public function tolak(PengajuanWilayahService $service): void
    {
        $pengajuan = PengajuanWilayah::findOrFail($this->tolakId);

        $this->authorize('tinjau', $pengajuan);

        $this->validate(
            ['catatanTolak' => ['required', 'string', 'min:10', 'max:1000']],
            [
                'catatanTolak.required' => 'Alasan penolakan wajib diisi.',
                'catatanTolak.min' => 'Jelaskan alasannya minimal 10 karakter, pemohon membacanya.',
            ],
        );

        $hasil = $this->jalankan(
            fn () => $service->tolak($pengajuan, auth()->user(), $this->catatanTolak),
            'Pengajuan ditolak dan pemohon diberi tahu alasannya.',
        );

        if ($hasil !== null) {
            $this->reset(['tolakId', 'catatanTolak']);
        }
    }

    /**
     * Unduh surat bukti kewenangan.
     *
     * Berkasnya di disk privat, jadi ia harus dialirkan lewat aksi
     * bertoken seperti ini. Menaruhnya di disk publik akan membuat surat
     * berkop dinas, lengkap dengan nama dan nomor pejabat, bisa dibuka
     * siapa saja yang menebak nama berkasnya.
     */
    public function unduhSurat(int $id)
    {
        $pengajuan = PengajuanWilayah::findOrFail($id);

        $this->authorize('lihatSurat', $pengajuan);

        if (! Storage::disk('local')->exists($pengajuan->surat_path)) {
            $this->pesanGalat('Berkas surat tidak ditemukan di peladen.');

            return null;
        }

        return Storage::disk('local')->download(
            $pengajuan->surat_path,
            'surat-pengajuan-'.$pengajuan->id.'.'.pathinfo($pengajuan->surat_path, PATHINFO_EXTENSION),
        );
    }

    public function render()
    {
        $query = PengajuanWilayah::query()
            ->with(['wilayah.parent', 'peninjau:id,name'])
            ->latest('id');

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        return view('livewire.admin.pengajuan-wilayah-manager', [
            'daftar' => $query->paginate(10),
            'statusTersedia' => StatusPengajuanWilayah::options(),
            'jumlahMenunggu' => PengajuanWilayah::query()
                ->where('status', StatusPengajuanWilayah::Diajukan)
                ->count(),
        ]);
    }
}
