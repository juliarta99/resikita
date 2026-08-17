<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Marketplace\KeranjangRequest;
use App\Http\Resources\ProdukResource;
use App\Http\Resources\UmkmResource;
use App\Models\Keranjang;
use App\Models\Produk;
use App\Services\Marketplace\KeranjangService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KeranjangController extends Controller
{
    public function __construct(
        private readonly KeranjangService $keranjang,
    ) {}

    /**
     * Isi keranjang, sudah dikelompokkan per toko.
     *
     * Bentuknya sengaja mencerminkan pemecahan pesanan saat checkout,
     * supaya pengguna melihat sejak awal bahwa belanja dari tiga toko
     * berarti tiga ongkos kirim, bukan terkejut di layar pembayaran.
     */
    public function index(Request $request): JsonResponse
    {
        $dikeluarkan = $this->keranjang->bersihkanYangTidakTersedia($request->user());
        $grup = $this->keranjang->isiTerkelompok($request->user());

        return ApiResponse::sukses(
            [
                'toko' => $grup->map(fn (array $baris): array => [
                    'umkm' => (new UmkmResource($baris['umkm']))->resolve(),
                    'subtotal' => $baris['subtotal'],
                    'berat_gram' => $baris['berat_gram'],
                    'item' => $baris['item']->map(fn (Keranjang $isi): array => [
                        'id' => $isi->id,
                        'qty' => $isi->qty,
                        'subtotal' => $isi->subtotal(),
                        'produk' => (new ProdukResource($isi->produk))->resolve(),
                    ])->values(),
                ])->values(),
                'total_item' => $this->keranjang->jumlahItem($request->user()),
                'total_belanja' => $grup->sum(fn (array $b): int => $b['subtotal']),
            ],
            $dikeluarkan === []
                ? null
                : 'Beberapa produk sudah tidak tersedia dan dikeluarkan dari keranjang: '.implode(', ', $dikeluarkan),
        );
    }

    public function tambah(KeranjangRequest $request): JsonResponse
    {
        $produk = Produk::findOrFail($request->integer('produk_id'));

        $this->keranjang->tambah($request->user(), $produk, $request->integer('qty'));

        return ApiResponse::sukses(
            ['total_item' => $this->keranjang->jumlahItem($request->user())],
            'Produk ditambahkan ke keranjang.',
        );
    }

    public function ubah(KeranjangRequest $request): JsonResponse
    {
        $produk = Produk::findOrFail($request->integer('produk_id'));

        $this->keranjang->ubahQty($request->user(), $produk, $request->integer('qty'));

        return ApiResponse::sukses(
            ['total_item' => $this->keranjang->jumlahItem($request->user())],
            'Keranjang diperbarui.',
        );
    }

    public function hapus(Request $request, Produk $produk): JsonResponse
    {
        $this->keranjang->hapus($request->user(), $produk);

        return ApiResponse::sukses(
            ['total_item' => $this->keranjang->jumlahItem($request->user())],
            'Produk dikeluarkan dari keranjang.',
        );
    }

    public function kosongkan(Request $request): JsonResponse
    {
        $this->keranjang->kosongkan($request->user());

        return ApiResponse::kosong('Keranjang dikosongkan.');
    }
}
