<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            // Sengaja tanpa aturan `exists`. Menolak email yang tidak
            // terdaftar di lapis validasi akan membocorkan siapa saja
            // yang punya akun di Resikita.
            'email' => ['required', 'email:rfc'],
            'kode' => ['required', 'string', 'digits:6'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }
}
