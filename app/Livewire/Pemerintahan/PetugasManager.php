<?php

declare(strict_types=1);

namespace App\Livewire\Pemerintahan;

use App\Enums\Role;
use App\Livewire\Concerns\MemberiUmpanBalik;
use App\Models\User;
use App\Models\Wilayah;
use App\Services\Auth\AkunService;
use App\Services\Wilayah\WilayahScopeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Pengelolaan akun petugas lapangan.
 *
 * Pembatasan wilayah dijaga dua kali dan sengaja begitu: daftar yang
 * ditampilkan dibatasi WilayahScopeService, dan pembuatan akun ditolak
 * AkunService kalau wilayahnya di luar kewenangan pembuat. Yang pertama
 * mencegah melihat, yang kedua mencegah bertindak, satu saja tidak
 * cukup, karena id wilayah bisa dikirim langsung tanpa lewat daftar.
 */
#[Title('Petugas Lapangan')]
class PetugasManager extends Component
{
    use MemberiUmpanBalik;
    use WithPagination;

    public string $cari = '';

    public bool $formTerbuka = false;

    public ?int $petugasId = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $wilayahId = '';

    public function updatedCari(): void
    {
        $this->resetPage();
    }

    // ----------------------------------------------------------------
    // Formulir
    // ----------------------------------------------------------------

    public function bukaForm(?int $id = null): void
    {
        $this->resetValidation();
        $this->reset(['petugasId', 'name', 'email', 'phone', 'wilayahId']);

        if ($id !== null) {
            $petugas = $this->dalamCakupan()->findOrFail($id);

            $this->petugasId = $petugas->id;
            $this->name = $petugas->name;
            $this->email = $petugas->email;
            $this->phone = $petugas->phone ?? '';
            $this->wilayahId = (string) ($petugas->wilayah_id ?? '');
        } else {
            $this->wilayahId = (string) (auth()->user()->wilayah_id ?? '');
        }

        $this->formTerbuka = true;
    }

    public function tutupForm(): void
    {
        $this->formTerbuka = false;
        $this->resetValidation();
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:150'],
            'email' => [
                'required', 'email', 'max:191',
                Rule::unique('users', 'email')->ignore($this->petugasId)->whereNull('deleted_at'),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'wilayahId' => ['required', 'integer'],
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'name' => 'nama', 'email' => 'email',
            'phone' => 'nomor telepon', 'wilayahId' => 'wilayah',
        ];
    }

    public function simpan(AkunService $akun): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone ?: null,
            'wilayah_id' => (int) $this->wilayahId,
        ];

        /*
         * Kata sandi tidak pernah ditentukan di sini. AkunService mengisi
         * nilai acak yang tidak dikirimkan ke mana pun, dan petugas
         * menyetel kata sandinya sendiri lewat alur lupa kata sandi.
         * Dengan begitu kredensial pertama tidak pernah melintas di
         * WhatsApp grup, dan atasan yang membuatkan akun tidak pernah
         * tahu kata sandi bawahannya.
         */
        $hasil = $this->petugasId === null
            ? $this->jalankan(
                fn () => $akun->buatPetugas(auth()->user(), $data),
                'Akun petugas dibuat. Minta yang bersangkutan memakai menu "Lupa kata sandi" untuk menyetel kata sandinya sendiri.',
            )
            : $this->jalankan(
                fn () => $akun->perbarui($this->dalamCakupan()->findOrFail($this->petugasId), $data),
                'Data petugas diperbarui.',
            );

        if ($hasil !== null) {
            $this->tutupForm();
        }
    }

    // ----------------------------------------------------------------
    // Status akun
    // ----------------------------------------------------------------

    public function ubahStatus(int $id, AkunService $akun): void
    {
        $petugas = $this->dalamCakupan()->findOrFail($id);

        $this->jalankan(
            fn () => $petugas->is_active ? $akun->nonaktifkan($petugas) : $akun->aktifkan($petugas),
            $petugas->is_active
                ? 'Akun dinonaktifkan. Sesi di ponselnya ikut diputus.'
                : 'Akun diaktifkan kembali.',
        );
    }

    /**
     * Query petugas dalam cakupan kewenangan pengguna.
     *
     * @return Builder<User>
     */
    private function dalamCakupan()
    {
        return app(WilayahScopeService::class)
            ->applyPengguna(User::query()->denganRole(Role::Petugas), auth()->user());
    }

    public function render()
    {
        $query = $this->dalamCakupan()->with('wilayah:id,nama,tingkat')->orderBy('name');

        if (trim($this->cari) !== '') {
            $kata = trim($this->cari);
            $query->where(fn ($q) => $q->where('name', 'like', "%{$kata}%")->orWhere('email', 'like', "%{$kata}%"));
        }

        return view('livewire.pemerintahan.petugas-manager', [
            'daftar' => $query->paginate(15),
            'wilayahTersedia' => $this->wilayahTersedia(),
        ]);
    }

    /**
     * Wilayah yang boleh dipilih sebagai penempatan petugas.
     *
     * @return Collection<int, Wilayah>
     */
    private function wilayahTersedia()
    {
        $ids = app(WilayahScopeService::class)->idDalamCakupan(auth()->user());

        return $ids === []
            ? collect()
            : Wilayah::query()->whereIn('id', $ids)->orderBy('tingkat')->orderBy('nama')->get();
    }
}
