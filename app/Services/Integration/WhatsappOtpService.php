<?php

namespace App\Services\Integration;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Pengirim OTP WhatsApp.
 * Driver:
 *  - 'log'   : dev — hanya menulis ke log (tidak butuh gateway).
 *  - 'fonnte': produksi — kirim via API Fonnte (butuh FONNTE_TOKEN).
 *
 * Set di config/services.php:
 *   'whatsapp' => ['driver' => env('WHATSAPP_DRIVER','log'), 'fonnte_token' => env('FONNTE_TOKEN')],
 */
class WhatsappOtpService
{
    public function send(string $phone, string $message): void
    {
        $driver = config('services.whatsapp.driver', 'log');

        if ($driver === 'fonnte') {
            Http::withHeaders(['Authorization' => config('services.whatsapp.fonnte_token')])
                ->asForm()
                ->post('https://api.fonnte.com/send', [
                    'target'  => $phone,
                    'message' => $message,
                ]);
            return;
        }

        Log::info("[WA OTP] ke {$phone}: {$message}");
    }
}