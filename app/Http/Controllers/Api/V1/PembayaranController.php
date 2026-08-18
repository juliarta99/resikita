<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Marketplace\PesananService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Callback pembayaran dari Midtrans.
 *
 * Endpoint ini terbuka tanpa autentikasi karena dipanggil peladen
 * Midtrans, bukan oleh pengguna. Yang menjaganya adalah tanda tangan
 * sha512 yang diperiksa MidtransService, tanpa itu, siapa pun yang
 * menebak nomor pesanan bisa menyatakan sebuah pesanan sudah dibayar.
 */
class PembayaranController extends Controller
{
    public function __construct(
        private readonly PesananService $pesanan,
    ) {}

    public function notifikasiMidtrans(Request $request): JsonResponse
    {
        $this->pesanan->terapkanNotifikasiPembayaran($request->all());

        // Midtrans hanya membaca kode status HTTP-nya. Selama tanda
        // tangan sah, jawaban selalu 200 supaya penyedia tidak
        // mengirim ulang notifikasi yang sudah kita proses.
        return ApiResponse::kosong('Notifikasi diterima.');
    }
}
