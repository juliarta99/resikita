<?php

declare(strict_types=1);

namespace App\Http\Requests\Klasifikasi;

use App\Http\Requests\ApiRequest;

class KlasifikasiRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            // Gambar dikirim ke Gemini sebagai inline_data base64,
            // sehingga ukurannya dibatasi lebih ketat daripada unggahan
            // biasa: muatan yang terlalu besar memperlambat klasifikasi
            // yang justru harus sinkron.
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'foto.required' => 'Foto sampah wajib diunggah.',
            'foto.max' => 'Ukuran foto maksimal 4 MB. Coba ambil ulang dengan resolusi lebih rendah.',
        ];
    }
}
