<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rules\Password;

class GantiPasswordRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'password_lama' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            ...parent::attributes(),
            'password_lama' => 'kata sandi lama',
            'password' => 'kata sandi baru',
        ];
    }
}
