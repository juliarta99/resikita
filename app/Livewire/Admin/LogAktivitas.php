<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\LogAktivitas as ModelLog;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Jejak tindakan pengguna.
 *
 * Sepenuhnya baca saja, dan itu bukan kelalaian fitur. Log yang bisa
 * disunting atau dihapus dari antarmuka berhenti menjadi bukti, satu
 * baris yang hilang cukup untuk membuat seluruh berkasnya tidak bisa
 * dipercaya saat ada yang perlu ditelusuri.
 *
 * Alamat IP dan user agent ikut disimpan sejak Resikita. Keduanya yang
 * membedakan "akun ini melakukan X" dari "seseorang yang memegang akun
 * ini melakukan X dari perangkat yang tidak biasa".
 */
#[Title('Log Aktivitas')]
class LogAktivitas extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $cari = '';

    #[Url(as: 'aksi', except: '')]
    public string $aksi = '';

    public function updated(string $properti): void
    {
        if ($properti !== 'page') {
            $this->resetPage();
        }
    }

    public function render()
    {
        $query = ModelLog::query()->with('user:id,name,email')->latest('id');

        if ($this->aksi !== '') {
            $query->aksi($this->aksi);
        }

        if (trim($this->cari) !== '') {
            $kata = trim($this->cari);
            $query->where(fn ($q) => $q
                ->where('deskripsi', 'like', "%{$kata}%")
                ->orWhere('ip_address', 'like', "%{$kata}%")
                ->orWhereHas('user', fn ($u) => $u
                    ->where('name', 'like', "%{$kata}%")
                    ->orWhere('email', 'like', "%{$kata}%")));
        }

        return view('livewire.admin.log-aktivitas', [
            'daftar' => $query->paginate(30),
            // Daftar aksi diambil dari data yang benar-benar ada, bukan
            // dari daftar tetap yang perlahan menyimpang dari kenyataan.
            'aksiTersedia' => ModelLog::query()
                ->select('aksi')
                ->distinct()
                ->orderBy('aksi')
                ->pluck('aksi', 'aksi'),
        ]);
    }
}
