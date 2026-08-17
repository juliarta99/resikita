<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\JenisKelamin;
use App\Livewire\Concerns\MemberiUmpanBalik;
use App\Services\Auth\AkunService;
use App\Services\Auth\AuthService;
use App\Support\Unggahan;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Profil akun, dipakai seluruh panel.
 *
 * Satu komponen untuk tujuh role. Tidak ada satu pun bagian halaman ini
 * yang bergantung pada role: setiap pengguna mengubah namanya, emailnya,
 * dan kata sandinya dengan aturan yang sama. Menduplikasinya per panel
 * hanya akan melahirkan tujuh salinan yang perlahan berbeda.
 */
#[Title('Profil')]
class Profil extends Component
{
    use MemberiUmpanBalik;
    use WithFileUploads;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $tanggalLahir = '';

    public string $jenisKelamin = '';

    public $avatarBaru;

    public string $passwordLama = '';

    public string $passwordBaru = '';

    public string $passwordBaru_confirmation = '';

    public function mount(): void
    {
        $pengguna = auth()->user();

        $this->name = $pengguna->name;
        $this->email = $pengguna->email;
        $this->phone = $pengguna->phone ?? '';
        $this->tanggalLahir = $pengguna->tanggal_lahir?->format('Y-m-d') ?? '';
        $this->jenisKelamin = $pengguna->jenis_kelamin?->value ?? '';
    }

    // ----------------------------------------------------------------
    // Data diri
    // ----------------------------------------------------------------

    public function simpanProfil(AkunService $akun): void
    {
        $pengguna = auth()->user();

        $this->validate([
            'name' => ['required', 'string', 'min:3', 'max:150'],
            'email' => [
                'required', 'email', 'max:191',
                Rule::unique('users', 'email')->ignore($pengguna->id)->whereNull('deleted_at'),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'tanggalLahir' => ['nullable', 'date', 'before:today'],
            'jenisKelamin' => ['nullable', Rule::enum(JenisKelamin::class)],
            'avatarBaru' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], attributes: [
            'name' => 'nama',
            'tanggalLahir' => 'tanggal lahir',
            'jenisKelamin' => 'jenis kelamin',
            'avatarBaru' => 'foto profil',
        ]);

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone ?: null,
            'tanggal_lahir' => $this->tanggalLahir ?: null,
            'jenis_kelamin' => $this->jenisKelamin ?: null,
        ];

        if ($this->avatarBaru !== null) {
            $data['avatar_path'] = Unggahan::simpan($this->avatarBaru, 'avatar');
        }

        $emailBerubah = $this->email !== $pengguna->email;

        $this->jalankan(
            fn () => $akun->perbarui($pengguna, $data),
            $emailBerubah
                ? 'Profil disimpan. Email baru perlu diverifikasi ulang sebelum bisa dipakai memulihkan akun.'
                : 'Profil disimpan.',
        );

        $this->reset('avatarBaru');
    }

    // ----------------------------------------------------------------
    // Kata sandi
    // ----------------------------------------------------------------

    public function gantiPassword(AuthService $auth): void
    {
        $this->validate([
            'passwordLama' => ['required', 'string'],
            'passwordBaru' => ['required', 'confirmed', Password::defaults()],
        ], attributes: [
            'passwordLama' => 'kata sandi lama',
            'passwordBaru' => 'kata sandi baru',
        ]);

        $hasil = $this->jalankan(
            function () use ($auth): bool {
                $auth->gantiPassword(auth()->user(), $this->passwordLama, $this->passwordBaru);

                return true;
            },
            'Kata sandi diganti. Sesi di perangkat lain ikut diputus.',
        );

        if ($hasil !== null) {
            $this->reset(['passwordLama', 'passwordBaru', 'passwordBaru_confirmation']);
        }
    }

    public function render()
    {
        return view('livewire.profil', [
            'pengguna' => auth()->user()->fresh(['wilayah', 'bankSampah', 'umkm']),
            'jenisKelaminTersedia' => JenisKelamin::options(),
        ]);
    }
}
