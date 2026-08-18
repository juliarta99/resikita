<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\AturanBisnisException;
use App\Http\Controllers\Controller;
use App\Http\Requests\BankSampah\HargaSampahRequest;
use App\Http\Requests\BankSampah\MulaiSetoranRequest;
use App\Http\Requests\BankSampah\TambahItemSetoranRequest;
use App\Http\Resources\BankSampahHargaResource;
use App\Http\Resources\PenggunaRingkasResource;
use App\Http\Resources\SetoranSampahResource;
use App\Models\BankSampah;
use App\Models\BankSampahHarga;
use App\Models\SetoranSampah;
use App\Services\Wallet\SetoranService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Operasional bank sampah: katalog harga dan transaksi setoran.
 */
class BankSampahController extends Controller
{
    public function __construct(
        private readonly SetoranService $setoran,
    ) {}

    public function harga(Request $request): JsonResponse
    {
        $bankSampah = $this->unitPengguna($request);

        return ApiResponse::sukses(
            BankSampahHargaResource::collection(
                $bankSampah->harga()->orderBy('jenis_sampah')->get(),
            )->resolve(),
        );
    }

    public function simpanHarga(HargaSampahRequest $request): JsonResponse
    {
        $bankSampah = $this->unitPengguna($request);
        $this->authorize('aturHarga', $bankSampah);

        $harga = $bankSampah->harga()->create($request->validated());

        return ApiResponse::dibuat(
            (new BankSampahHargaResource($harga))->resolve(),
            'Jenis sampah ditambahkan ke katalog.',
        );
    }

    public function perbaruiHarga(HargaSampahRequest $request, BankSampahHarga $harga): JsonResponse
    {
        $this->authorize('aturHarga', $harga->bankSampah);

        $harga->update($request->validated());

        return ApiResponse::resource(
            new BankSampahHargaResource($harga->fresh()),
            'Harga diperbarui.',
        );
    }

    /** Pindai QR nasabah sebelum menimbang. */
    public function cariNasabah(MulaiSetoranRequest $request): JsonResponse
    {
        $nasabah = $this->setoran->cariNasabah($request->string('kode_qr')->toString());

        return ApiResponse::resource(new PenggunaRingkasResource($nasabah));
    }

    public function mulaiSetoran(MulaiSetoranRequest $request): JsonResponse
    {
        $bankSampah = $this->unitPengguna($request);
        $this->authorize('layaniSetoran', $bankSampah);

        $nasabah = $this->setoran->cariNasabah($request->string('kode_qr')->toString());

        $setoran = $this->setoran->mulai($bankSampah, $request->user(), $nasabah);

        return ApiResponse::dibuat(
            (new SetoranSampahResource($setoran->load(['nasabah', 'bankSampah'])))->resolve(),
            'Transaksi setoran dimulai.',
        );
    }

    public function tambahItem(TambahItemSetoranRequest $request, SetoranSampah $setoran): JsonResponse
    {
        $this->authorize('layaniSetoran', $setoran->bankSampah);

        $harga = BankSampahHarga::findOrFail($request->integer('harga_id'));

        $this->setoran->tambahItem($setoran, $harga, $request->float('berat'));

        return ApiResponse::resource(
            new SetoranSampahResource($setoran->fresh(['item', 'nasabah', 'bankSampah'])),
            'Item ditambahkan.',
        );
    }

    public function selesaikanSetoran(Request $request, SetoranSampah $setoran): JsonResponse
    {
        $this->authorize('layaniSetoran', $setoran->bankSampah);

        $selesai = $this->setoran->selesaikan($setoran);

        return ApiResponse::resource(
            new SetoranSampahResource($selesai->load(['item', 'nasabah', 'bankSampah'])),
            sprintf(
                'Setoran selesai. Saldo Rp %s masuk ke dompet nasabah.',
                number_format($selesai->total_nilai, 0, ',', '.'),
            ),
        );
    }

    public function batalkanSetoran(Request $request, SetoranSampah $setoran): JsonResponse
    {
        $this->authorize('layaniSetoran', $setoran->bankSampah);

        $this->setoran->batalkan($setoran, $request->input('alasan'));

        return ApiResponse::kosong('Setoran dibatalkan.');
    }

    public function riwayat(Request $request): JsonResponse
    {
        $bankSampah = $this->unitPengguna($request);

        $query = SetoranSampah::query()
            ->where('bank_sampah_id', $bankSampah->id)
            ->with(['nasabah:id,name,avatar_path', 'item'])
            ->latest('id');

        return ApiResponse::halaman(
            SetoranSampahResource::collection(
                $query->paginate($request->integer('per_page', 15)),
            ),
        );
    }

    public function rekap(Request $request): JsonResponse
    {
        $bankSampah = $this->unitPengguna($request);

        return ApiResponse::sukses(
            $this->setoran->rekap($bankSampah, $request->input('sejak')),
        );
    }

    /** Unit bank sampah tempat pengguna terdaftar sebagai pengelola. */
    private function unitPengguna(Request $request): BankSampah
    {
        $bankSampah = $request->user()->bankSampah;

        if ($bankSampah === null) {
            throw AturanBisnisException::tidakBerwenang(
                'Akun Anda belum terhubung dengan unit bank sampah mana pun.',
            );
        }

        return $bankSampah;
    }
}
