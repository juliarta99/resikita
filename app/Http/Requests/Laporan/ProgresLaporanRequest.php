<?php

declare(strict_types=1);

namespace App\Http\Requests\Laporan;

use App\Enums\StatusProgres;
use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class ProgresLaporanRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status_progres' => ['required', Rule::enum(StatusProgres::class)],
            'catatan' => ['nullable', 'string', 'max:2000'],

            // Wajib saat menyelesaikan. Aturan bisnisnya juga ditegakkan
            // di LaporanService, karena jalur web tidak melewati kelas ini.
            'foto_bukti' => [
                Rule::requiredIf(fn (): bool => $this->input('status_progres') === StatusProgres::Selesai->value),
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:4096',
            ],

            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'foto_bukti.required' => 'Foto bukti wajib dilampirkan saat menyelesaikan laporan.',
        ];
    }
}
