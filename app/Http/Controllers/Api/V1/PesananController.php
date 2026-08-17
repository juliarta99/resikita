<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Marketplace\CheckoutRequest;
use App\Http\Requests\Marketplace\HitungOngkirRequest;
use App\Http\Requests\Marketplace\UlasanRequest;
use App\Http\Resources\PesananResource;
use App\Http\Resources\UlasanResource;
use App\Models\Pesanan;
use App\Models\Umkm;
use App\Services\Integration\ShippingService;
use App\Services\Marketplace\CheckoutService;
use App\Services\Marketplace\PesananService;
use App\Support\ApiResponse;
use App\Support\Unggahan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PesananController extends Controller
{
    public function __construct(
        private readonly CheckoutService $checkout,
        private readonly PesananService $pesanan,
        private readonly ShippingService $shipping,
    ) {}

    public function cariTujuan(Request $request): JsonResponse
    {
        $request->validate(['cari' => ['required', 'string', 'min:3', 'max:100']]);

        return ApiResponse::sukses(
            $this->shipping->cariTujuan($request->string('cari')->toString()),
        );
    }

    /**
     * Ongkir satu paket dari satu toko.
     *
     * Jalur pintas untuk tombol "cek ongkir" di halaman produk. Alur
     * checkout memakai `pratinjau()`, yang menghitung seluruh toko yang
     * hendak dibayar sekaligus.
     */
    public function hitungOngkir(HitungOngkirRequest $request): JsonResponse
    {
        $umkm = Umkm::findOrFail($request->integer('umkm_id'));

        return ApiResponse::sukses(
            $this->shipping->hitung(
                $this->shipping->originUntuk($umkm),
                $request->integer('destination_id'),
                $request->integer('berat_gram'),
                $request->input('kurir'),
            ),
        );
    }

    /**
     * Rincian checkout per toko beserta pilihan ongkirnya.
     *
     * `umkm_ids` menyaring ke toko yang benar-benar akan dibayar.
     * Dibiarkan opsional supaya tanpa parameter itu hasilnya sama
     * dengan sebelumnya: seluruh isi keranjang.
     */
    public function pratinjau(Request $request): JsonResponse
    {
        $request->validate([
            'destination_id' => ['required', 'integer', 'min:1'],
            'umkm_ids' => ['sometimes', 'array', 'min:1'],
            'umkm_ids.*' => ['integer', 'min:1'],
        ]);

        $grup = $this->checkout->pratinjau(
            $request->user(),
            $request->integer('destination_id'),
            $request->has('umkm_ids') ? array_map(intval(...), $request->input('umkm_ids')) : null,
        );

        return ApiResponse::sukses(
            $grup->map(fn (array $baris): array => [
                'umkm_id' => $baris['umkm']->id,
                'umkm_nama' => $baris['umkm']->nama,
                'subtotal' => $baris['subtotal'],
                'berat_gram' => $baris['berat_gram'],

                // Tiap paket berangkat dari alamat penjualnya sendiri.
                // Ditampilkan supaya pembeli tahu ongkir yang berbeda
                // antar toko bukan kekeliruan hitung.
                'asal' => $baris['asal'],

                'pilihan_ongkir' => $baris['pilihan_ongkir'],
            ])->values()->all(),
        );
    }

    public function index(Request $request): JsonResponse
    {
        $query = Pesanan::query()
            ->where('user_id', $request->user()->id)
            ->untukDaftar()
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        return ApiResponse::halaman(
            PesananResource::collection($query->paginate($request->integer('per_page', 15))),
        );
    }

    public function show(Pesanan $pesanan): JsonResponse
    {
        $this->authorize('view', $pesanan);

        return ApiResponse::resource(
            new PesananResource($pesanan->load(['item.produk', 'umkm'])),
        );
    }

    public function checkout(CheckoutRequest $request): JsonResponse
    {
        $this->authorize('create', Pesanan::class);

        $pesanan = $this->checkout->checkout($request->user(), $request->validated());

        return ApiResponse::dibuat(
            PesananResource::collection($pesanan)->resolve(),
            $pesanan->count() > 1
                ? "Belanja Anda dipecah menjadi {$pesanan->count()} pesanan karena berasal dari toko yang berbeda."
                : 'Pesanan berhasil dibuat.',
        );
    }

    public function batalkan(Request $request, Pesanan $pesanan): JsonResponse
    {
        $this->authorize('batalkan', $pesanan);

        $this->checkout->batalkan($pesanan, $request->input('alasan'));

        return ApiResponse::resource(
            new PesananResource($pesanan->fresh(['item', 'umkm'])),
            'Pesanan dibatalkan. Stok dikembalikan dan dana dikembalikan ke saldo Anda bila sudah terbayar.',
        );
    }

    public function bayarUlang(Pesanan $pesanan): JsonResponse
    {
        $this->authorize('bayarUlang', $pesanan);

        $diperbarui = $this->pesanan->bayarUlang($pesanan);

        return ApiResponse::sukses(
            ['snap_token' => $diperbarui->snap_token],
            'Token pembayaran baru diterbitkan.',
        );
    }

    public function terima(Pesanan $pesanan): JsonResponse
    {
        $this->authorize('view', $pesanan);

        $selesai = $this->pesanan->tandaiSelesai($pesanan);

        return ApiResponse::resource(
            new PesananResource($selesai->load(['item', 'umkm'])),
            'Terima kasih. Pesanan ditandai selesai dan dana diteruskan ke penjual.',
        );
    }

    public function tulisUlasan(UlasanRequest $request, Pesanan $pesanan): JsonResponse
    {
        $this->authorize('ulas', $pesanan);

        $data = $request->safe()->except('foto');

        if ($request->hasFile('foto')) {
            $data['foto_path'] = Unggahan::simpan($request->file('foto'), 'ulasan');
        }

        $ulasan = $this->pesanan->tulisUlasan($pesanan->load('item'), $request->user(), $data);

        return ApiResponse::dibuat(
            (new UlasanResource($ulasan->load('user')))->resolve(),
            'Terima kasih atas ulasannya.',
        );
    }
}
