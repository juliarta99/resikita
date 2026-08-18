<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Http\Requests\ApiRequest;

class LoginRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc'],
            'password' => ['required', 'string'],

            // Nama perangkat muncul di daftar sesi aktif, sehingga
            // pengguna bisa mengenali dan mencabut token dari ponsel yang
            // hilang.
            'nama_perangkat' => ['nullable', 'string', 'max:100'],
        ];
    }
}
