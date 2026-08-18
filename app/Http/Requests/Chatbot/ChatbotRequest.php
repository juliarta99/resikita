<?php

declare(strict_types=1);

namespace App\Http\Requests\Chatbot;

use App\Enums\SumberInput;
use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class ChatbotRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'pesan' => ['required', 'string', 'min:2', 'max:2000'],
            'sesi_id' => ['nullable', 'integer', 'exists:chat_sesi,id'],

            // Menandai pertanyaan yang didiktekan, bukan diketik.
            'sumber_input' => ['nullable', Rule::enum(SumberInput::class)],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            ...parent::attributes(),
            'pesan' => 'pertanyaan',
        ];
    }
}
