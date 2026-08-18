<?php

declare(strict_types=1);

namespace App\Services\Integration;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Pengiriman pesan WhatsApp lewat Fonnte.
 *
 * Skema lama punya dua kelas yang melakukan hal sama, FonnteService dan
 * WhatsappOtpService, dengan penanganan galat dan normalisasi nomor
 * yang berbeda. Keduanya digabung di sini.
 *
 * Driver `log` dipakai di pengembangan dan pengujian supaya tidak ada
 * pesan sungguhan terkirim ke nomor orang saat menjalankan seeder atau
 * uji. Pengiriman nyata hanya terjadi kalau WHATSAPP_DRIVER=fonnte.
 */
class FonnteService
{
    /**
     * Kirim pesan. Mengembalikan rujukan pesan dari penyedia bila ada,
     * untuk disimpan di `notifikasi.provider_ref` sebagai jejak.
     */
    public function kirim(string $phone, string $pesan): ?string
    {
        $tujuan = $this->normalisasiNomor($phone);

        if ($this->driver() !== 'fonnte') {
            Log::info('[WhatsApp:log] pesan tidak dikirim sungguhan', [
                'tujuan' => $tujuan,
                'pesan' => $pesan,
            ]);

            return 'log:'.substr(md5($tujuan.$pesan), 0, 12);
        }

        $response = Http::withHeaders(['Authorization' => (string) config('services.fonnte.token')])
            ->asForm()
            ->timeout(15)
            ->post((string) config('services.fonnte.url'), [
                'target' => $tujuan,
                'message' => $pesan,
            ]);

        if ($response->failed()) {
            Log::error('Fonnte gagal mengirim pesan', [
                'tujuan' => $tujuan,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        return $response->json('id.0') ?? $response->json('id');
    }

    /**
     * Ubah 081234567890 menjadi 6281234567890.
     *
     * Fonnte menolak nomor berformat lokal, dan pengguna Indonesia
     * hampir selalu mengetik dengan awalan 0.
     */
    public function normalisasiNomor(string $phone): string
    {
        $angka = preg_replace('/\D/', '', $phone) ?? '';

        if (str_starts_with($angka, '0')) {
            return '62'.substr($angka, 1);
        }

        if (str_starts_with($angka, '8')) {
            return '62'.$angka;
        }

        return $angka;
    }

    private function driver(): string
    {
        return (string) config('services.whatsapp.driver', 'log');
    }
}
