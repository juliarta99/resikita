<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Laporan\ProgresLaporanRequest;
use App\Http\Resources\LaporanPenugasanResource;
use App\Http\Resources\LaporanProgresResource;
use App\Http\Resources\LaporanResource;
use App\Models\Laporan;
use App\Models\LaporanPenugasan;
use App\Services\Laporan\LaporanService;
use App\Support\ApiResponse;
use App\Support\Unggahan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Penugasan lapangan untuk role `petugas`.
 *
 * Seluruh endpoint di sini otomatis terbatas pada penugasan milik
 * petugas yang sedang masuk, bukan lewat filter di query, melainkan
 * lewat relasi penugasan itu sendiri.
 */
class PetugasController extends Controller
{
    public function __construct(
        private readonly LaporanService $laporan,
    ) {}

    public function penugasan(Request $request): JsonResponse
    {
        $query = LaporanPenugasan::query()
            ->milikPetugas($request->user()->id)
            ->with(['laporan' => fn ($q) => $q->untukDaftar()])
            ->latest('id');

        if ($request->boolean('hanya_aktif', true)) {
            $query->aktif();
        }

        return ApiResponse::halaman(
            LaporanPenugasanResource::collection(
                $query->paginate($request->integer('per_page', 15)),
            ),
        );
    }

    public function detail(Laporan $laporan): JsonResponse
    {
        $this->authorize('kerjakan', $laporan);

        $laporan->load(['kategori', 'pelapor', 'foto', 'desa', 'kabupaten', 'progres.petugas']);

        return ApiResponse::resource(new LaporanResource($laporan));
    }

    public function catatProgres(ProgresLaporanRequest $request, Laporan $laporan): JsonResponse
    {
        $this->authorize('kerjakan', $laporan);

        $data = $request->safe()->except('foto_bukti');

        if ($request->hasFile('foto_bukti')) {
            $data['foto_bukti'] = Unggahan::simpan($request->file('foto_bukti'), 'progres');
        }

        $progres = $this->laporan->catatProgres($laporan, $request->user(), $data);

        return ApiResponse::dibuat(
            (new LaporanProgresResource($progres->load('petugas')))->resolve(),
            'Progres tercatat.',
        );
    }
}
