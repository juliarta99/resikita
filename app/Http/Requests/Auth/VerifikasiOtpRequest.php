<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Http\Requests\ApiRequest;

class VerifikasiOtpRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'kode' => ['required', 'string', 'digits:6'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'kode.digits' => 'Kode verifikasi terdiri dari 6 angka.',
        ];
    }
}
