<?php

declare(strict_types=1);

namespace App\Http\Requests\Dompet;

use App\Http\Requests\ApiRequest;

class AjukanPenarikanRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            // Batas minimum dan maksimum diperiksa di PenarikanService,
            // bukan di sini: keduanya berasal dari config dan berlaku
            // untuk jalur web juga.
            'jumlah' => ['required', 'integer', 'min:1'],
            'metode' => ['nullable', 'string', 'max:50'],
            'nama_bank' => ['nullable', 'string', 'max:100'],
            'no_rekening' => ['required', 'string', 'regex:/^[0-9]{6,25}$/'],
            'atas_nama' => ['required', 'string', 'min:3', 'max:150'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'no_rekening.regex' => 'Nomor rekening hanya boleh berisi angka, 6 sampai 25 digit.',
        ];
    }
}
