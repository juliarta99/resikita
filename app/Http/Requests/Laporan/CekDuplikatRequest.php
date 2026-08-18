<?php

declare(strict_types=1);

namespace App\Http\Requests\Laporan;

use App\Http\Requests\ApiRequest;

class CekDuplikatRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'kategori_id' => ['nullable', 'integer', 'exists:laporan_kategori,id'],
        ];
    }
}
