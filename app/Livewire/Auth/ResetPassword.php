<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Livewire\Concerns\MemberiUmpanBalik;
use App\Services\Auth\AuthService;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Setel kata sandi baru dengan kode pemulihan.
 *
 * Pemeriksaan kode, masa berlaku, dan pencabutan seluruh token Sanctum
 * ada di AuthService, sama persis dengan yang dipakai kanal mobile.
 */
#[Layout('components.layouts.auth')]
#[Title('Setel Kata Sandi Baru')]
class ResetPassword extends Component
{
    use MemberiUmpanBalik;

    #[Url]
    public string $email = '';

    public string $kode = '';

    public string $password = '';

    public string $password_confirmation = '';

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'kode' => ['required', 'string', 'digits:6'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'kode' => 'kode pemulihan',
            'password' => 'kata sandi baru',
        ];
    }

    public function setel(AuthService $auth): void
    {
        $this->validate();

        $berhasil = $auth->resetPassword($this->email, $this->kode, $this->password);

        if (! $berhasil) {
            /*
             * Satu pesan untuk kode salah maupun kode kedaluwarsa.
             * Membedakan keduanya memberi tahu penebak bahwa kodenya
             * pernah benar, dan itu mempersempit ruang tebakannya.
             */
            throw ValidationException::withMessages([
                'kode' => 'Kode pemulihan tidak cocok atau sudah kedaluwarsa. Minta kode baru.',
            ]);
        }

        session()->flash('pesan-masuk', 'Kata sandi berhasil diganti. Silakan masuk dengan kata sandi baru.');

        $this->redirectRoute('masuk', navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.reset-password');
    }
}
