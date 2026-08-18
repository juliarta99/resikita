<?php

declare(strict_types=1);

namespace App\Http\Requests\BankSampah;

use App\Enums\KategoriSampah;
use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class HargaSampahRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $bankSampahId = $this->user()->bank_sampah_id;
        $hargaId = $this->route('harga')?->id;

        return [
            'jenis_sampah' => [
                'required',
                'string',
                'max:150',
                Rule::unique('bank_sampah_harga', 'jenis_sampah')
                    ->where('bank_sampah_id', $bankSampahId)
                    ->ignore($hargaId),
            ],
            'kategori' => ['required', Rule::enum(KategoriSampah::class)],
            'satuan' => ['nullable', 'string', 'max:20'],
            // Rupiah penuh sebagai integer.
            'harga_per_satuan' => ['required', 'integer', 'min:1', 'max:100000000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'jenis_sampah.unique' => 'Jenis sampah ini sudah ada di katalog Anda.',
        ];
    }
}
