<?php

declare(strict_types=1);

namespace App\Http\Requests\Marketplace;

use App\Enums\MetodeBayar;
use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

/**
 * Checkout memecah keranjang menjadi satu pesanan per toko, sehingga
 * pilihan kurir dikirim per UMKM, bukan satu pilihan untuk seluruh
 * keranjang. Bentuk `pengiriman` mencerminkan pemecahan itu.
 *
 * Kuncinya sekaligus menentukan toko mana yang dibayar: toko yang tidak
 * disebut tetap tinggal di keranjang. Karena itu `min:1` di sini bukan
 * sekadar penjagaan bentuk, nol entri berarti tidak ada yang dipesan.
 */
class CheckoutRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'nama_penerima' => ['required', 'string', 'min:3', 'max:150'],
            'phone_penerima' => ['required', 'string', 'regex:/^0[0-9]{8,13}$/'],
            'alamat_kirim' => ['required', 'string', 'min:10', 'max:500'],
            'destination_id' => ['required', 'integer', 'min:1'],
            'metode_bayar' => ['required', Rule::enum(MetodeBayar::class)],

            'pengiriman' => ['required', 'array', 'min:1'],
            'pengiriman.*.kurir' => ['required', 'string', 'max:50'],
            'pengiriman.*.layanan' => ['required', 'string', 'max:100'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'destination_id.required' => 'Alamat tujuan belum dipilih. Cari alamat lebih dulu lewat pencarian tujuan.',
            'pengiriman.required' => 'Pilih setidaknya satu toko beserta pengirimannya.',
            'pengiriman.min' => 'Pilih setidaknya satu toko beserta pengirimannya.',
        ];
    }
}
