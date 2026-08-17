<?php

declare(strict_types=1);

namespace App\Http\Requests\Laporan;

use App\Enums\SumberInput;
use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class SimpanLaporanRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'kategori_id' => ['required', 'integer', 'exists:laporan_kategori,id'],
            'judul' => ['required', 'string', 'min:5', 'max:191'],
            'deskripsi' => ['required', 'string', 'min:10', 'max:5000'],

            // Menandai laporan yang dibuat lewat masukan suara. Angkanya
            // yang membuat klaim inklusivitas bisa diuji.
            'deskripsi_sumber' => ['nullable', Rule::enum(SumberInput::class)],

            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'alamat' => ['nullable', 'string', 'max:255'],

            'foto' => ['nullable', 'array', 'max:5'],
            'foto.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],

            // Diisi ketika pengguna memilih menggabungkan laporannya ke
            // laporan kembar yang ditawarkan sistem.
            'gabung_ke_id' => ['nullable', 'integer', 'exists:laporan,id'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'latitude.required' => 'Titik lokasi laporan wajib ditentukan.',
            'longitude.required' => 'Titik lokasi laporan wajib ditentukan.',
            'foto.max' => 'Maksimal 5 foto per laporan.',
            'deskripsi.min' => 'Deskripsi terlalu singkat. Jelaskan sedikit lebih rinci agar petugas mudah menindaklanjuti.',
        ];
    }
}
