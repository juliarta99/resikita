<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function kategori()
    {
        $data = ProductCategory::withCount(['products' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('nama')->get()
            ->map(fn ($c) => ['id' => $c->id, 'nama' => $c->nama, 'jumlah' => $c->products_count]);

        return response()->json(['data' => $data]);
    }

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

        $paginator = $q->paginate(12);

        $productIds = collect($paginator->items())->pluck('id');
        try {
            $ratings = \App\Models\Review::whereIn('product_id', $productIds)
                ->selectRaw('product_id, AVG(rating) as avg, COUNT(*) as cnt')
                ->groupBy('product_id')->get()->keyBy('product_id');
        } catch (\Throwable $e) {
            $ratings = collect(); // mis. kolom product_id belum ada (migrasi belum jalan)
        }

        $paginator->through(fn ($p) => [
            'id' => $p->id, 'nama' => $p->nama, 'harga' => (float) $p->harga, 'stok' => $p->stok,
            'umkm' => ['id' => $p->umkm?->id, 'nama' => $p->umkm?->nama],
            'gambar' => $p->images->first() ? asset('storage/' . $p->images->first()->path) : null,
            'rating' => round((float) ($ratings[$p->id]->avg ?? 0), 1),
            'jumlah_ulasan' => (int) ($ratings[$p->id]->cnt ?? 0),
        ]);

        return response()->json($paginator);
    }

    public function show(Product $product)
    {
        $product->load(['umkm', 'kategori', 'images']);

        try {
            $stat = \App\Models\Review::where('product_id', $product->id)
                ->selectRaw('AVG(rating) as avg, COUNT(*) as cnt')->first();
            $ulasan = \App\Models\Review::where('product_id', $product->id)->with('user')->latest()->take(5)->get()
                ->map(fn ($r) => [
                    'id' => $r->id, 'nama' => $r->user?->name ?? 'Pengguna',
                    'rating' => (int) $r->rating, 'komentar' => $r->komentar,
                    'tanggal' => $r->created_at?->toIso8601String(),
                ]);
        } catch (\Throwable $e) {
            $stat = null;
            $ulasan = collect();
        }

        return response()->json(['data' => [
            'id' => $product->id, 'nama' => $product->nama, 'harga' => (float) $product->harga,
            'stok' => $product->stok, 'deskripsi' => $product->deskripsi, 'berat' => $product->berat,
            'kategori' => $product->kategori?->nama,
            'umkm' => ['id' => $product->umkm?->id, 'nama' => $product->umkm?->nama, 'alamat' => $product->umkm?->alamat],
            'gambar' => $product->images->map(fn ($im) => asset('storage/' . $im->path))->values(),
            'rating' => round((float) ($stat->avg ?? 0), 1),
            'jumlah_ulasan' => (int) ($stat->cnt ?? 0),
            'ulasan' => $ulasan,
        ]]);
    }
}