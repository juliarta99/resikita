<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\Role;
use App\Livewire\Concerns\MemberiUmpanBalik;
use App\Models\User;
use App\Services\Auth\AkunService;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Manajemen pengguna lintas peran.
 *
 * Tidak ada kolom NIK di mana pun pada halaman ini, dan itu bukan
 * kelalaian. Kolom `users.nik` dihapus seluruhnya pada migrasi ke
 * Resikita: sistem tidak memerlukannya, sementara menyimpannya mengubah
 * satu kebocoran basis data biasa menjadi insiden data kependudukan
 * (CLAUDE.md 4.2).
 */
#[Title('Manajemen Pengguna')]
class PenggunaManager extends Component
{
    use MemberiUmpanBalik;
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $cari = '';

    #[Url(as: 'peran', except: '')]
    public string $peran = '';

    #[Url(as: 'nonaktif', except: false)]
    public bool $hanyaNonaktif = false;

    public function updated(string $properti): void
    {
        if ($properti !== 'page') {
            $this->resetPage();
        }
    }

    public function ubahStatus(int $id, AkunService $akun): void
    {
        $pengguna = User::findOrFail($id);

        if ($pengguna->is($this->penggunaSaatIni())) {
            $this->pesanGalat('Anda tidak bisa menonaktifkan akun Anda sendiri.');

            return;
        }

        /*
         * Super admin tidak bisa dinonaktifkan lewat halaman ini.
         * Kalau bisa, satu admin yang keliru mengeklik bisa mengunci
         * seluruh platform dari pemiliknya sendiri.
         */
        if ($pengguna->hasRole(Role::SuperAdmin->value)) {
            $this->pesanGalat('Akun super admin tidak dapat dinonaktifkan dari halaman ini.');

            return;
        }

        $this->jalankan(
            fn () => $pengguna->is_active ? $akun->nonaktifkan($pengguna) : $akun->aktifkan($pengguna),
            $pengguna->is_active
                ? 'Akun dinonaktifkan. Token aplikasinya ikut dicabut.'
                : 'Akun diaktifkan kembali.',
        );
    }

    private function penggunaSaatIni(): User
    {
        return auth()->user();
    }

    public function render()
    {
        $query = User::query()
            ->with(['roles:id,name', 'wilayah:id,nama,tingkat', 'bankSampah:id,nama', 'umkm:id,nama'])
            ->latest('id');

        if ($this->peran !== '') {
            $query->whereHas('roles', fn ($q) => $q->where('name', $this->peran));
        }

        if ($this->hanyaNonaktif) {
            $query->where('is_active', false);
        }

        if (trim($this->cari) !== '') {
            $kata = trim($this->cari);
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$kata}%")
                ->orWhere('email', 'like', "%{$kata}%")
                ->orWhere('phone', 'like', "%{$kata}%"));
        }

        return view('livewire.admin.pengguna-manager', [
            'daftar' => $query->paginate(20),
            'peranTersedia' => Role::options(),
        ]);
    }
}
