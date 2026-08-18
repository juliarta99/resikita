<?php

declare(strict_types=1);

namespace App\Services\Integration;

use App\Enums\StatusPembayaran;
use App\Exceptions\AturanBisnisException;
use Illuminate\Support\Facades\Log;
use Midtrans\Config;
use Midtrans\Snap;
use Throwable;

/**
 * Pembayaran lewat Midtrans Snap.
 *
 * Melayani pesanan marketplace maupun iuran TPS, karena keduanya
 * memakai jalur yang sama.
 */
class MidtransService
{
    public function __construct()
    {
        Config::$serverKey = (string) config('services.midtrans.server_key');
        Config::$isProduction = (bool) config('services.midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    /**
     * Terbitkan Snap token untuk satu tagihan.
     *
     * @param  string  $orderId  Kode unik tagihan, mis. RSK-ORD-20260814-A1B2C3
     * @param  int  $jumlah  Total rupiah sebagai integer
     * @param  array{first_name: string, email: string, phone: ?string}  $pembeli
     * @param  array<int, array{id: string, price: int, quantity: int, name: string}>  $item
     */
    public function buatSnapToken(string $orderId, int $jumlah, array $pembeli, array $item = []): string
    {
        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $jumlah,
            ],
            'customer_details' => $pembeli,
        ];

        if ($item !== []) {
            $params['item_details'] = $item;
        }

        try {
            return Snap::getSnapToken($params);
        } catch (Throwable $e) {
            Log::error('Midtrans gagal menerbitkan Snap token', [
                'order_id' => $orderId,
                'pesan' => $e->getMessage(),
            ]);

            throw AturanBisnisException::karena(
                'Layanan pembayaran sedang tidak tersedia. Coba lagi beberapa saat lagi.',
                503,
            );
        }
    }

    /**
     * Verifikasi dan terjemahkan callback dari Midtrans.
     *
     * Tanda tangan diperiksa sebelum apa pun dibaca. Endpoint notifikasi
     * terbuka tanpa autentikasi, itu memang cara kerja callback
     * server-to-server, sehingga tanda tangan inilah satu-satunya yang
     * membedakan pemberitahuan asli dari kiriman siapa saja yang menebak
     * nomor pesanan dan mengaku sudah membayar.
     *
     * @param  array<string, mixed>  $payload
     * @return array{order_id: string, transaction_id: ?string, status: StatusPembayaran, raw_status: string}
     */
    public function bacaNotifikasi(array $payload): array
    {
        $this->pastikanTandaTanganSah($payload);

        $statusMentah = (string) ($payload['transaction_status'] ?? '');

        return [
            'order_id' => (string) $payload['order_id'],
            'transaction_id' => $payload['transaction_id'] ?? null,
            'status' => StatusPembayaran::dariMidtrans(
                $statusMentah,
                $payload['fraud_status'] ?? null,
            ),
            'raw_status' => $statusMentah,
        ];
    }

    /**
     * Tanda tangan Midtrans: sha512(order_id + status_code +
     * gross_amount + server_key).
     *
     * @param  array<string, mixed>  $payload
     */
    private function pastikanTandaTanganSah(array $payload): void
    {
        foreach (['order_id', 'status_code', 'gross_amount', 'signature_key'] as $wajib) {
            if (! isset($payload[$wajib])) {
                throw AturanBisnisException::karena('Notifikasi pembayaran tidak lengkap.', 400);
            }
        }

        $diharapkan = hash(
            'sha512',
            $payload['order_id']
            .$payload['status_code']
            .$payload['gross_amount']
            .config('services.midtrans.server_key'),
        );

        // hash_equals, bukan ===, agar perbandingan tidak membocorkan
        // informasi lewat selisih waktu eksekusi.
        if (! hash_equals($diharapkan, (string) $payload['signature_key'])) {
            Log::warning('Notifikasi Midtrans dengan tanda tangan tidak sah ditolak', [
                'order_id' => $payload['order_id'],
            ]);

            throw AturanBisnisException::karena('Tanda tangan notifikasi tidak sah.', 403);
        }
    }
}
