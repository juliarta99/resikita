<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rules\Password;

class DaftarRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:150'],

            // Email adalah identitas utama sejak NIK dihapus, jadi
            // keunikannya ditegakkan di sini dan di index basis data.
            'email' => ['required', 'email:rfc', 'max:191', 'unique:users,email'],

            'password' => ['required', 'confirmed', Password::min(8)],

            // Opsional: hanya dipakai untuk notifikasi WhatsApp.
            'phone' => ['nullable', 'string', 'regex:/^0[0-9]{8,13}$/'],

            'wilayah_id' => ['nullable', 'integer', 'exists:wilayah,id'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'email.unique' => 'Email ini sudah terdaftar. Silakan masuk atau gunakan email lain.',
            'phone.regex' => 'Nomor telepon harus diawali 0 dan terdiri dari 9 sampai 14 angka.',
        ];
    }
}
