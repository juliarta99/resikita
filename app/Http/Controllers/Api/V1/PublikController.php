<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\WilayahResource;
use App\Models\Wilayah;
use App\Services\Analitik\StatistikPublikService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Angka nasional untuk layar beranda aplikasi ponsel.
 *
 * Sumbernya StatistikPublikService, sama persis dengan yang mengisi
 * beranda web. Menghitungnya ulang di sini akan melahirkan dua angka
 * berbeda untuk hal yang sama, dan yang satu pasti keliru.
 *
 * Terbuka tanpa token: pengunjung yang belum punya akun harus bisa
 * menilai apakah sistem ini berguna sebelum diminta mendaftar.
 */
class PublikController extends Controller
{
    public function __construct(
        private readonly StatistikPublikService $statistik,
    ) {}

    public function statistik(): JsonResponse
    {
        return ApiResponse::sukses([
            'ringkasan' => $this->statistik->ringkasan(),
            'fitur_suara' => $this->statistik->fiturSuara(),
        ]);
    }

    /**
     * Titik fasilitas untuk peta.
     *
     * Hanya bank sampah dan TPS. Titik laporan sengaja tidak ikut:
     * koordinat laporan menunjuk tempat yang bisa jadi halaman rumah
     * seseorang, dan menyebarnya di peta terbuka mengubah alat
     * pelaporan menjadi alat menunjuk tetangga.
     */
    public function peta(): JsonResponse
    {
        return ApiResponse::sukses([
            'titik' => $this->statistik->titikFasilitas(),
        ]);
    }

    /** Wilayah yang pemerintahnya sudah bergabung. */
    public function wilayahTerdaftar(): JsonResponse
    {
        return ApiResponse::sukses(
            WilayahResource::collection(
                Wilayah::query()
                    ->terverifikasi()
                    ->with('parent')
                    ->orderBy('tingkat')
                    ->orderBy('nama')
                    ->get(),
            )->resolve(),
        );
    }
}
