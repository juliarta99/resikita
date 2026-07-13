<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Integration\RajaOngkirService;
use Illuminate\Http\Request;

class ShippingController extends Controller
{
    public function __construct(private RajaOngkirService $ongkir)
    {
    }

    public function cariTujuan(Request $request)
    {
        $request->validate(['q' => 'required|string|min:3']);

        try {
            $data = $this->ongkir->searchDestination($request->query('q'));
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Gagal mencari tujuan pengiriman.'], 503);
        }

        return response()->json(['data' => $data]);
    }

    public function hitung(Request $request)
    {
        $data = $request->validate([
            'destination_id'     => 'required|integer',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty'        => 'required|integer|min:1',
            'courier'            => 'nullable|string|max:100',
        ]);

        // Berat total (gram); default 250 g/produk bila berat kosong
        $produk = Product::whereIn('id', collect($data['items'])->pluck('product_id'))->get()->keyBy('id');
        $berat = 0;
        foreach ($data['items'] as $it) {
            $p = $produk[$it['product_id']] ?? null;
            $satuan = $p && $p->berat ? (int) $p->berat : 250;
            $berat += $satuan * $it['qty'];
        }
        $berat = max($berat, 100);

        try {
            $hasil = $this->ongkir->cost($data['destination_id'], $berat, $data['courier'] ?? 'jne:jnt:sicepat');
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Gagal menghitung ongkir.'], 503);
        }

        return response()->json(['data' => ['berat_gram' => $berat, 'opsi' => $hasil]]);
    }
}