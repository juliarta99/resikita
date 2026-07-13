<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $q = Product::where('is_active', true)->with(['umkm', 'images'])->latest();

        if ($umkm = $request->query('umkm_id')) {
            $q->where('umkm_id', $umkm);
        }
        if ($kat = $request->query('kategori_id')) {
            $q->where('kategori_id', $kat);
        }
        if ($s = $request->query('q')) {
            $q->where('nama', 'like', "%{$s}%");
        }

        $data = $q->paginate(12)->through(fn ($p) => [
            'id' => $p->id, 'nama' => $p->nama, 'harga' => (float) $p->harga, 'stok' => $p->stok,
            'umkm' => ['id' => $p->umkm?->id, 'nama' => $p->umkm?->nama],
            'gambar' => $p->images->first() ? asset('storage/' . $p->images->first()->path) : null,
        ]);

        return response()->json($data);
    }

    public function show(Product $product)
    {
        $product->load(['umkm', 'kategori', 'images']);

        return response()->json(['data' => [
            'id' => $product->id, 'nama' => $product->nama, 'harga' => (float) $product->harga,
            'stok' => $product->stok, 'deskripsi' => $product->deskripsi, 'berat' => $product->berat,
            'kategori' => $product->kategori?->nama,
            'umkm' => ['id' => $product->umkm?->id, 'nama' => $product->umkm?->nama, 'alamat' => $product->umkm?->alamat],
            'gambar' => $product->images->map(fn ($im) => asset('storage/' . $im->path))->values(),
        ]]);
    }
}