<?php

declare(strict_types=1);

namespace App\Http\Requests\BankSampah;

use App\Http\Requests\ApiRequest;

class MulaiSetoranRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            // ULID 26 karakter hasil pemindaian QR nasabah.
            'kode_qr' => ['required', 'string', 'size:26'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'kode_qr.size' => 'Kode QR nasabah tidak valid. Pindai ulang kode dari aplikasi nasabah.',
        ];
    }
}
