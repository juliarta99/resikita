<?php
// app/Services/Integration/MidtransService.php

namespace App\Services\Integration;

use Midtrans\Config;
use Midtrans\Snap;
use RuntimeException;

class MidtransService
{
    public function __construct()
    {
        Config::$serverKey    = config('services.midtrans.server_key');
        Config::$isProduction = (bool) config('services.midtrans.is_production');
        Config::$isSanitized  = true;
        Config::$is3ds        = true;
    }

    /**
     * Buat Snap token untuk order / langganan.
     *
     * @param string $orderId     unik, mis. "ORDER-21" atau "SUB-12"
     * @param int    $grossAmount total rupiah (integer)
     * @param array  $customer    ['first_name' => ..., 'email' => ..., 'phone' => ...]
     * @param array  $items       [['id','price','quantity','name'], ...]
     */
    public function createSnapToken(string $orderId, int $grossAmount, array $customer, array $items = []): string
    {
        $params = [
            'transaction_details' => [
                'order_id'     => $orderId,
                'gross_amount' => $grossAmount,
            ],
            'customer_details' => $customer,
        ];

        if (! empty($items)) {
            $params['item_details'] = $items;
        }

        return Snap::getSnapToken($params);
    }

    /**
     * Verifikasi & baca callback Midtrans.
     * Return status ternormalisasi: paid | pending | failed.
     *
     * @throws RuntimeException jika signature tidak valid
     */
    public function handleNotification(array $payload): array
    {
        $expected = hash('sha512',
            $payload['order_id']
            . $payload['status_code']
            . $payload['gross_amount']
            . config('services.midtrans.server_key')
        );

        if (! hash_equals($expected, $payload['signature_key'] ?? '')) {
            throw new RuntimeException('Signature Midtrans tidak valid.');
        }

        $status = $payload['transaction_status'] ?? '';
        $fraud  = $payload['fraud_status'] ?? 'accept';

        $normalized = match (true) {
            in_array($status, ['capture', 'settlement']) && $fraud === 'accept' => 'paid',
            in_array($status, ['pending'])                                      => 'pending',
            default                                                             => 'failed',
        };

        return [
            'order_id'       => $payload['order_id'],
            'transaction_id' => $payload['transaction_id'] ?? null,
            'status'         => $normalized,
            'raw_status'     => $status,
        ];
    }
}