<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\KategoriSampah;
use App\Http\Controllers\Controller;
use App\Http\Requests\Klasifikasi\KlasifikasiRequest;
use App\Http\Resources\KlasifikasiSampahResource;
use App\Models\KlasifikasiSampah;
use App\Services\Klasifikasi\KlasifikasiService;
use App\Support\ApiResponse;
use App\Support\Unggahan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Klasifikasi sampah dari foto.
 *
 * Satu-satunya endpoint AI yang sengaja dibiarkan sinkron bersama
 * chatbot (CLAUDE.md 3 butir 8): pengguna sedang berdiri memegang
 * sampahnya dan menunggu jawaban. Menjadwalkannya lewat antrean berarti
 * ia harus menutup layar lalu kembali lagi, dan fitur ini kehilangan
 * seluruh gunanya.
 */
class KlasifikasiController extends Controller
{
    public function __construct(
        private readonly KlasifikasiService $klasifikasi,
    ) {}

    public function store(KlasifikasiRequest $request): JsonResponse
    {
        $this->authorize('create', KlasifikasiSampah::class);

        $path = Unggahan::simpan($request->file('foto'), 'klasifikasi');

        $hasil = $this->klasifikasi->klasifikasikan($request->user(), $path);

        return ApiResponse::resource(
            new KlasifikasiSampahResource($hasil),
            'Sampah berhasil dikenali.',
            201,
        );
    }

    public function riwayat(Request $request): JsonResponse
    {
        return ApiResponse::halaman(
            KlasifikasiSampahResource::collection(
                $this->klasifikasi->riwayat(
                    $request->user(),
                    KategoriSampah::tryFrom((string) $request->query('kategori')),
                    $request->integer('per_page', 15),
                ),
            ),
        );
    }

    public function ringkasan(Request $request): JsonResponse
    {
        return ApiResponse::sukses($this->klasifikasi->ringkasan($request->user()));
    }

    public function show(KlasifikasiSampah $klasifikasi): JsonResponse
    {
        $this->authorize('view', $klasifikasi);

        return ApiResponse::resource(new KlasifikasiSampahResource($klasifikasi));
    }

    public function destroy(KlasifikasiSampah $klasifikasi): JsonResponse
    {
        $this->authorize('delete', $klasifikasi);

        $this->klasifikasi->hapus($klasifikasi);

        return ApiResponse::kosong('Riwayat klasifikasi dihapus.');
    }
}
