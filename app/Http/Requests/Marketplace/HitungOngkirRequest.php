<?php

declare(strict_types=1);

namespace App\Http\Requests\Marketplace;

use App\Http\Requests\ApiRequest;

/**
 * Permintaan hitung ongkir satu paket.
 *
 * `umkm_id` wajib, dan klien tidak pernah mengirim titik asalnya sendiri.
 * Asal diturunkan peladen dari toko yang bersangkutan karena dua alasan:
 * tarif yang dihitung dari titik yang bukan lokasi penjual adalah angka
 * yang salah, dan origin yang boleh ditentukan klien berarti pembeli bisa
 * menekan ongkirnya sendiri dengan mengaku berangkat dari kota tetangga.
 */
class HitungOngkirRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'umkm_id' => ['required', 'integer', 'exists:umkm,id'],
            'destination_id' => ['required', 'integer', 'min:1'],
            'berat_gram' => ['required', 'integer', 'min:1', 'max:1000000'],
            'kurir' => ['nullable', 'string', 'max:100'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'umkm_id.required' => 'Toko pengirim belum ditentukan.',
            'umkm_id.exists' => 'Toko yang dimaksud tidak ditemukan.',
            'destination_id.required' => 'Alamat tujuan belum dipilih. Cari alamat lebih dulu lewat pencarian tujuan.',
            'berat_gram.required' => 'Berat paket wajib diisi.',
        ];
    }
}
