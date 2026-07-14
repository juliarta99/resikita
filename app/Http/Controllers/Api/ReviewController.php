<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /** Beri ulasan per produk untuk pesanan selesai (hanya pemilik). */
    public function store(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403, 'Bukan pesanan Anda.');

        if ($order->status !== 'selesai') {
            return response()->json(['message' => 'Ulasan hanya untuk pesanan yang sudah selesai.'], 422);
        }

        $data = $request->validate([
            'ulasan'              => 'required|array|min:1',
            'ulasan.*.product_id' => 'required|integer',
            'ulasan.*.rating'     => 'required|integer|min:1|max:5',
            'ulasan.*.komentar'   => 'nullable|string|max:500',
        ]);

        $order->load('items');
        $validIds = $order->items->pluck('product_id')->filter()->map(fn ($id) => (int) $id)->all();

        $dibuat = 0;
        foreach ($data['ulasan'] as $u) {
            $pid = (int) $u['product_id'];
            if (! in_array($pid, $validIds, true)) {
                continue;
            }
            if (Review::where('order_id', $order->id)->where('product_id', $pid)->exists()) {
                continue;
            }
            Review::create([
                'user_id'    => $request->user()->id,
                'order_id'   => $order->id,
                'product_id' => $pid,
                'umkm_id'    => $order->umkm_id,
                'rating'     => $u['rating'],
                'komentar'   => $u['komentar'] ?? null,
            ]);
            $dibuat++;
        }

        if ($dibuat === 0) {
            return response()->json(['message' => 'Semua produk pada pesanan ini sudah diulas.'], 422);
        }

        return response()->json(['message' => 'Terima kasih atas ulasan Anda.', 'dibuat' => $dibuat], 201);
    }
}