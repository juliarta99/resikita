<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dompet\AjukanPenarikanRequest;
use App\Http\Resources\DompetTransaksiResource;
use App\Http\Resources\PenarikanSaldoResource;
use App\Http\Resources\SetoranSampahResource;
use App\Models\PenarikanSaldo;
use App\Models\SetoranSampah;
use App\Services\Wallet\DompetService;
use App\Services\Wallet\PenarikanService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DompetController extends Controller
{
    public function __construct(
        private readonly DompetService $dompet,
        private readonly PenarikanService $penarikan,
    ) {}

    public function saldo(Request $request): JsonResponse
    {
        $user = $request->user();

        return ApiResponse::sukses([
            // Nilai mentah integer rupiah; pemformatan di sisi klien.
            'saldo' => $this->dompet->saldo($user),
            'kode_qr' => $user->pastikanKodeQr(),
            'ringkasan_bulan_ini' => $this->dompet->ringkasan($user, now()->startOfMonth()->toDateTimeString()),
            'penarikan_minimum' => (int) config('resikita.dompet.penarikan_minimum'),
        ]);
    }

    public function transaksi(Request $request): JsonResponse
    {
        return ApiResponse::halaman(
            DompetTransaksiResource::collection(
                $this->dompet->mutasi($request->user())->paginate($request->integer('per_page', 20)),
            ),
        );
    }

    /** Riwayat setoran sampah milik pengguna sebagai nasabah. */
    public function setoran(Request $request): JsonResponse
    {
        $query = SetoranSampah::query()
            ->where('nasabah_id', $request->user()->id)
            ->with(['bankSampah', 'item'])
            ->latest('id');

        return ApiResponse::halaman(
            SetoranSampahResource::collection(
                $query->paginate($request->integer('per_page', 15)),
            ),
        );
    }

    public function riwayatPenarikan(Request $request): JsonResponse
    {
        $query = PenarikanSaldo::query()
            ->where('user_id', $request->user()->id)
            ->latest('id');

        return ApiResponse::halaman(
            PenarikanSaldoResource::collection(
                $query->paginate($request->integer('per_page', 15)),
            ),
        );
    }

    public function ajukanPenarikan(AjukanPenarikanRequest $request): JsonResponse
    {
        $this->authorize('create', PenarikanSaldo::class);

        $penarikan = $this->penarikan->ajukan($request->user(), $request->validated());

        return ApiResponse::dibuat(
            (new PenarikanSaldoResource($penarikan))->resolve(),
            'Pengajuan penarikan diterima. Saldo Anda sudah dipotong dan akan dikembalikan bila pengajuan ditolak.',
        );
    }
}
