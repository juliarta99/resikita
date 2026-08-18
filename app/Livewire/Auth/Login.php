<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Exceptions\AturanBisnisException;
use App\Livewire\Concerns\MemberiUmpanBalik;
use App\Services\Auth\AuthService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Masuk ke panel web.
 *
 * Pemeriksaan kredensial, pembatasan laju, dan penolakan akun nonaktif
 * seluruhnya ada di AuthService, yang sama persis dipakai endpoint
 * `POST /auth/login` untuk mobile. Komponen ini hanya mengurus apa yang
 * memang khas web: membuat sesi dan memilih ke mana pengguna diantar.
 *
 * Sanctum dipakai dalam mode token untuk mobile dan mode sesi untuk web
 * (CLAUDE.md 2). SPA mode tidak dipakai di mana pun.
 */
#[Layout('components.layouts.auth')]
#[Title('Masuk')]
class Login extends Component
{
    use MemberiUmpanBalik;

    public string $email = '';

    public string $password = '';

    public bool $ingatSaya = false;

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return ['email' => 'email', 'password' => 'kata sandi'];
    }

    public function masuk(AuthService $auth): void
    {
        $this->validate();

        try {
            $pengguna = $auth->autentikasi($this->email, $this->password);
        } catch (AturanBisnisException $e) {
            // Dilekatkan pada bidang email supaya pesannya muncul di
            // dekat formulir, bukan sebagai pemberitahuan melayang yang
            // hilang sendiri sebelum sempat dibaca.
            throw ValidationException::withMessages(['email' => $e->getMessage()]);
        }

        $role = $pengguna->roleUtama();

        /*
         * Masyarakat dan petugas lapangan tidak punya panel web
         * (CLAUDE.md 6.2). Menolak mereka dengan "email atau kata sandi
         * salah" akan menyesatkan, kredensialnya benar. Yang perlu
         * mereka tahu adalah pintunya ada di tempat lain.
         */
        if ($role === null || ! $role->punyaAksesWeb()) {
            throw ValidationException::withMessages([
                'email' => 'Akun ini dipakai lewat aplikasi Resikita di ponsel, bukan panel web.',
            ]);
        }

        Auth::login($pengguna, $this->ingatSaya);

        // Cegah pembajakan sesi: identitas sesi lama tidak boleh
        // membawa hak akses pengguna yang baru masuk. Diambil lewat
        // helper session(), bukan request()->session(), supaya komponen
        // ini juga bisa diuji tanpa melewati middleware web.
        session()->regenerate();

        $this->redirectRoute($role->routeDasbor(), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
