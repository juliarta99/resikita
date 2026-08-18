<?php

declare(strict_types=1);

namespace App\Http\Requests\Wilayah;

use App\Http\Requests\ApiRequest;

/**
 * Pengajuan pendaftaran wilayah.
 *
 * Terbuka untuk umum karena pemohonnya adalah pejabat daerah yang belum
 * punya akun Resikita. Yang menjaga mutunya bukan autentikasi,
 * melainkan surat bukti kewenangan yang wajib diunggah dan ditinjau
 * super admin.
 */
class AjukanWilayahRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'wilayah_id' => ['required', 'integer', 'exists:wilayah,id'],
            'pemohon_nama' => ['required', 'string', 'min:3', 'max:150'],
            'pemohon_jabatan' => ['required', 'string', 'min:3', 'max:150'],
            'pemohon_email' => ['required', 'email:rfc', 'max:191'],
            'pemohon_phone' => ['nullable', 'string', 'regex:/^0[0-9]{8,13}$/'],
            'instansi' => ['required', 'string', 'min:3', 'max:191'],
            'surat' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            ...parent::attributes(),
            'pemohon_nama' => 'nama pemohon',
            'pemohon_jabatan' => 'jabatan pemohon',
            'pemohon_email' => 'email pemohon',
            'pemohon_phone' => 'nomor telepon pemohon',
            'instansi' => 'instansi',
            'surat' => 'surat bukti kewenangan',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'surat.required' => 'Surat tugas atau surat keterangan kewenangan wajib diunggah.',
            'surat.mimes' => 'Surat harus berformat PDF, JPG, atau PNG.',
        ];
    }
}
