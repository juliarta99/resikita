<?php

namespace App\Livewire\Auth;

use App\Support\RoleRedirect;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.auth')]
class Login extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required')]
    public string $password = '';

    public bool $remember = false;

    public function login()
    {
        $this->validate();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi salah.',
            ]);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => 'Akun Anda nonaktif. Hubungi admin.',
            ]);
        }

        // Masyarakat hanya lewat aplikasi mobile
        if ($user->hasAnyRole(['masyarakat'])) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => 'Akun ini hanya dapat digunakan lewat aplikasi mobile.',
            ]);
        }

        request()->session()->regenerate();

        return redirect()->intended(RoleRedirect::for($user));
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
