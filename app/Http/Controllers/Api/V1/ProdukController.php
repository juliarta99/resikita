<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProdukResource;
use App\Http\Resources\UlasanResource;
use App\Models\Produk;
use App\Models\ProdukKategori;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProdukController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Produk::query()
            ->tersedia()
            ->untukKatalog()
            ->whereHas('umkm', fn ($q) => $q->aktif());

        if ($request->filled('kategori')) {
            $query->whereHas('kategori', fn ($q) => $q->where('slug', $request->string('kategori')->toString()));
        }

        if ($request->filled('umkm_id')) {
            $query->where('umkm_id', $request->integer('umkm_id'));
        }

        if ($request->filled('cari')) {
            $cari = $request->string('cari')->toString();
            $query->where(fn ($q) => $q
                ->where('nama', 'like', "%$cari%")
                ->orWhere('bahan_baku', 'like', "%$cari%"));
        }

        if ($request->filled('harga_min')) {
            $query->where('harga', '>=', $request->integer('harga_min'));
        }

        if ($request->filled('harga_maks')) {
            $query->where('harga', '<=', $request->integer('harga_maks'));
        }

        $query->orderBy(
            match ($request->string('urut')->toString()) {
                'termurah', 'termahal' => 'harga',
                'terlaris' => 'ulasan_count',
                default => 'id',
            },
            $request->string('urut')->toString() === 'termurah' ? 'asc' : 'desc',
        );

        return ApiResponse::halaman(
            ProdukResource::collection($query->paginate($request->integer('per_page', 20))),
        );
    }

    public function kategori(): JsonResponse
    {
        return ApiResponse::sukses(
            ProdukKategori::query()
                ->withCount(['produk' => fn ($q) => $q->tersedia()])
                ->orderBy('nama')
                ->get()
                ->map(fn (ProdukKategori $k): array => [
                    'id' => $k->id,
                    'nama' => $k->nama,
                    'slug' => $k->slug,
                    'ikon' => $k->ikon,
                    'jumlah_produk' => $k->produk_count,
                ])
                ->all(),
        );
    }

    public function show(Produk $produk): JsonResponse
    {
        $this->authorize('view', $produk);

        $produk->load(['umkm.wilayah', 'kategori', 'foto'])
            ->loadCount('ulasan')
            ->loadAvg('ulasan as rating_rata', 'rating');

        return ApiResponse::resource(new ProdukResource($produk));
    }

    public function ulasan(Request $request, Produk $produk): JsonResponse
    {
        $query = $produk->ulasan()
            ->with('user:id,name,avatar_path')
            ->latest('id');

        return ApiResponse::halaman(
            UlasanResource::collection($query->paginate($request->integer('per_page', 10))),
        );
    }
}
