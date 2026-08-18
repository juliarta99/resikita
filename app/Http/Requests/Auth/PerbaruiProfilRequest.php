<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Enums\JenisKelamin;
use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class PerbaruiProfilRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'min:3', 'max:150'],
            'email' => [
                'sometimes',
                'email:rfc',
                'max:191',
                Rule::unique('users', 'email')->ignore($this->user()->id),
            ],
            'phone' => ['nullable', 'string', 'regex:/^0[0-9]{8,13}$/'],
            'tanggal_lahir' => ['nullable', 'date', 'before:today'],
            'jenis_kelamin' => ['nullable', Rule::enum(JenisKelamin::class)],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'wilayah_id' => ['nullable', 'integer', 'exists:wilayah,id'],
        ];
    }
}
