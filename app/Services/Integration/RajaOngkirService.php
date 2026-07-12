<?php
// app/Services/Integration/RajaOngkirService.php

namespace App\Services\Integration;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class RajaOngkirService
{
    private function client()
    {
        return Http::withHeaders([
            'key'    => config('services.rajaongkir.key'),
            'Accept' => 'application/json',
        ])->baseUrl(config('services.rajaongkir.base_url'));
    }

    /**
     * Cari id tujuan (district/subdistrict) dari kata kunci.
     * Dipakai user saat mengisi alamat kirim untuk mendapatkan destination id.
     */
    public function searchDestination(string $keyword, int $limit = 10): array
    {
        $response = $this->client()->get('/destination/domestic-destination', [
            'search' => $keyword,
            'limit'  => $limit,
            'offset' => 0,
        ]);

        if ($response->failed()) {
            throw new RuntimeException('RajaOngkir search error: ' . $response->body());
        }

        return $response->json('data', []);
    }

    /**
     * Hitung ongkir domestik.
     *
     * @param int    $destination id tujuan (dari searchDestination)
     * @param int    $weight      berat dalam gram
     * @param string $courier     mis. "jne:sicepat:jnt" (pisah dengan titik dua)
     */
    public function cost(int $destination, int $weight, string $courier = 'jne:jnt:sicepat'): array
    {
        $origin = (int) config('services.rajaongkir.origin_id');

        $response = $this->client()
            ->asForm()
            ->post('/calculate/domestic-cost', [
                'origin'      => $origin,
                'destination' => $destination,
                'weight'      => $weight,
                'courier'     => $courier,
                'price'       => 'lowest',
            ]);

        if ($response->failed()) {
            throw new RuntimeException('RajaOngkir cost error: ' . $response->body());
        }

        return $response->json('data', []);
    }
}