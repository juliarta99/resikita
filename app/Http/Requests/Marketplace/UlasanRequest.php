<?php

declare(strict_types=1);

namespace App\Http\Requests\Marketplace;

use App\Http\Requests\ApiRequest;

class UlasanRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'produk_id' => ['nullable', 'integer', 'exists:produk,id'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'komentar' => ['nullable', 'string', 'max:2000'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }
}
