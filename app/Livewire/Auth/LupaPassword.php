<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Livewire\Concerns\MemberiUmpanBalik;
use App\Services\Auth\AuthService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Permintaan kode pemulihan kata sandi.
 *
 * Selalu melapor berhasil, termasuk untuk email yang tidak terdaftar.
 * Kalau tidak, halaman ini berubah menjadi alat untuk memeriksa siapa
 * saja yang punya akun di Resikita. Perilaku itu ditegakkan di
 * AuthService, bukan di sini.
 */
#[Layout('components.layouts.auth')]
#[Title('Lupa Kata Sandi')]
class LupaPassword extends Component
{
    use MemberiUmpanBalik;

    public string $email = '';

    public bool $terkirim = false;

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return ['email' => ['required', 'email']];
    }

    public function kirim(AuthService $auth): void
    {
        $this->validate();

        $auth->mintaResetPassword($this->email);

        $this->terkirim = true;
    }

    public function render()
    {
        return view('livewire.auth.lupa-password');
    }
}
