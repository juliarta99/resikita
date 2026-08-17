<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;

/**
 * Amplop response API (CLAUDE.md 12).
 *
 * Bentuknya adalah kontrak dengan `resikita-mobile`. Setiap endpoint
 * melewati kelas ini supaya tidak ada satu pun response yang menyimpang
 * bentuknya, aplikasi mobile membaca `success`, `message`, `data`, dan
 * `meta` di posisi yang sama untuk semua endpoint.
 *
 * Mengubah bentuk di sini berarti mengubah kontrak. Jangan lakukan tanpa
 * memperbarui aplikasi mobile dan API-DOCS.md.
 */
final class ApiResponse
{
    /** Response tunggal berhasil. */
    public static function sukses(mixed $data = null, ?string $pesan = null, int $status = 200): JsonResponse
    {
        $badan = ['success' => true];

        if ($pesan !== null) {
            $badan['message'] = $pesan;
        }

        $badan['data'] = $data;

        return response()->json($badan, $status);
    }

    /** Response pembuatan sumber daya baru. */
    public static function dibuat(mixed $data = null, ?string $pesan = null): JsonResponse
    {
        return self::sukses($data, $pesan, 201);
    }

    /**
     * Response daftar berhalaman.
     *
     * `meta` sengaja hanya memuat lima kunci yang benar-benar dipakai
     * mobile untuk menggulir. Meta bawaan Laravel membawa `links` dan
     * `path` yang tidak berguna bagi klien non-web dan hanya memperbesar
     * muatan di jaringan seluler.
     */
    public static function halaman(AbstractPaginator|ResourceCollection $paginator, ?string $pesan = null): JsonResponse
    {
        $sumber = $paginator instanceof ResourceCollection
            ? $paginator->resource
            : $paginator;

        $badan = ['success' => true];

        if ($pesan !== null) {
            $badan['message'] = $pesan;
        }

        $badan['data'] = $paginator instanceof ResourceCollection
            ? $paginator->resolve()
            : $paginator->items();

        $badan['meta'] = [
            'current_page' => $sumber->currentPage(),
            'last_page' => $sumber->lastPage(),
            'per_page' => $sumber->perPage(),
            'total' => $sumber->total(),
        ];

        return response()->json($badan);
    }

    /**
     * Response gagal.
     *
     * @param  array<string, array<int, string>>  $errors
     */
    public static function gagal(string $pesan, int $status = 400, array $errors = []): JsonResponse
    {
        $badan = [
            'success' => false,
            'message' => $pesan,
        ];

        if ($errors !== []) {
            $badan['errors'] = $errors;
        }

        return response()->json($badan, $status);
    }

    /** @param array<string, array<int, string>> $errors */
    public static function validasiGagal(array $errors, string $pesan = 'Data yang dikirim tidak valid.'): JsonResponse
    {
        return self::gagal($pesan, 422, $errors);
    }

    public static function belumLogin(string $pesan = 'Anda harus masuk terlebih dahulu.'): JsonResponse
    {
        return self::gagal($pesan, 401);
    }

    public static function tidakBerwenang(string $pesan = 'Anda tidak berwenang melakukan tindakan ini.'): JsonResponse
    {
        return self::gagal($pesan, 403);
    }

    public static function tidakDitemukan(string $pesan = 'Data yang diminta tidak ditemukan.'): JsonResponse
    {
        return self::gagal($pesan, 404);
    }

    /** Response tanpa muatan, mis. setelah penghapusan. */
    public static function kosong(string $pesan): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $pesan,
            'data' => null,
        ]);
    }

    /** Bungkus satu API Resource ke dalam amplop. */
    public static function resource(JsonResource $resource, ?string $pesan = null, int $status = 200): JsonResponse
    {
        return self::sukses($resource->resolve(), $pesan, $status);
    }
}
