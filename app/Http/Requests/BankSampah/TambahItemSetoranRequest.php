<?php

declare(strict_types=1);

namespace App\Http\Requests\BankSampah;

use App\Http\Requests\ApiRequest;

class TambahItemSetoranRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'harga_id' => ['required', 'integer', 'exists:bank_sampah_harga,id'],
            // Timbangan bank sampah lazimnya berketelitian dua desimal.
            'berat' => ['required', 'numeric', 'min:0.01', 'max:10000'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            ...parent::attributes(),
            'harga_id' => 'jenis sampah',
            'berat' => 'berat',
        ];
    }
}
