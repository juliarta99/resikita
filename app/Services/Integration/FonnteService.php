<?php
// app/Services/Integration/FonnteService.php

namespace App\Services\Integration;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteService
{
    /**
     * Kirim pesan WhatsApp lewat Fonnte.
     */
    public function sendWa(string $phone, string $message): bool
    {
        $response = Http::withHeaders([
            'Authorization' => config('services.fonnte.token'),
        ])->asForm()->post(config('services.fonnte.url'), [
            'target'  => $this->normalize($phone),
            'message' => $message,
        ]);

        if ($response->failed()) {
            Log::error('Fonnte gagal kirim WA', [
                'phone' => $phone,
                'body'  => $response->body(),
            ]);
            return false;
        }

        return true;
    }

    /**
     * Kirim OTP verifikasi pendaftaran.
     */
    public function sendOtp(string $phone, string $code): bool
    {
        $message = "Kode verifikasi Niti Resik Anda: {$code}\n"
            . "Berlaku 5 menit. Jangan bagikan kode ini ke siapa pun.";

        return $this->sendWa($phone, $message);
    }

    /**
     * Notifikasi progress laporan ke pelapor.
     */
    public function sendProgressUpdate(string $phone, string $tiket, string $status): bool
    {
        $message = "Update laporan Niti Resik ({$tiket}):\n"
            . "Status: {$status}\n"
            . "Cek detailnya di aplikasi. Terima kasih.";

        return $this->sendWa($phone, $message);
    }

    /**
     * 081234567890 -> 6281234567890
     */
    private function normalize(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        return $phone;
    }
}