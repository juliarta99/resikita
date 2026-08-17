<?php

declare(strict_types=1);

namespace App\Http\Requests\Marketplace;

use App\Http\Requests\ApiRequest;

class KeranjangRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'produk_id' => ['required', 'integer', 'exists:produk,id'],
            'qty' => ['required', 'integer', 'min:1', 'max:99'],
        ];
    }
}
