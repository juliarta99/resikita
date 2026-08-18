<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\AturanBisnisException;
use App\Http\Controllers\Controller;
use App\Http\Resources\TpsIuranResource;
use App\Http\Resources\TpsResource;
use App\Models\Tps;
use App\Models\TpsIuran;
use App\Services\Tps\TpsService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TpsController extends Controller
{
    public function __construct(
        private readonly TpsService $tps,
    ) {}

    /** Keanggotaan TPS milik pengguna, beserta tagihan berjalan. */
    public function keanggotaanSaya(Request $request): JsonResponse
    {
        $anggota = $this->tps->keanggotaan($request->user());

        if ($anggota === null) {
            return ApiResponse::sukses(null, 'Anda belum terdaftar sebagai anggota TPS mana pun.');
        }

        return ApiResponse::sukses([
            'tps' => (new TpsResource($anggota->tps))->resolve(),
            'bergabung_at' => $anggota->bergabung_at?->toIso8601String(),
            'tagihan' => TpsIuranResource::collection(
                $anggota->iuran()->latest('periode')->limit(12)->get(),
            )->resolve(),
        ]);
    }

    public function gabung(Request $request, Tps $tps): JsonResponse
    {
        $anggota = $this->tps->gabung($tps, $request->user());

        return ApiResponse::dibuat(
            ['tps' => (new TpsResource($anggota->tps))->resolve()],
            "Anda terdaftar sebagai anggota {$tps->nama}.",
        );
    }

    public function keluar(Request $request): JsonResponse
    {
        $anggota = $this->tps->keanggotaan($request->user());

        if ($anggota === null) {
            throw AturanBisnisException::karena('Anda belum terdaftar di TPS mana pun.');
        }

        $this->tps->keluar($anggota);

        return ApiResponse::kosong('Keanggotaan TPS dihentikan.');
    }

    public function bayarIuran(Request $request, TpsIuran $iuran): JsonResponse
    {
        $lunas = $this->tps->bayarDenganSaldo($iuran, $request->user());

        return ApiResponse::resource(
            new TpsIuranResource($lunas),
            'Iuran lunas, dibayar dari saldo Resikita Anda.',
        );
    }
}
