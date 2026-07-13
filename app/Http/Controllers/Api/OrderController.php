<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\InsufficientBalanceException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\TpsSubscription;
use App\Services\Domain\WalletService;
use App\Services\Integration\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function __construct(private WalletService $wallet)
    {
    }

    public function store(Request $request, MidtransService $midtrans)
    {
        $data = $request->validate([
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty'        => 'required|integer|min:1',
            'metode_bayar'       => 'required|in:saldo,midtrans',
            'alamat_kirim'       => 'required|string|max:500',
            'destination_id'     => 'nullable|integer',
            'kurir'              => 'nullable|string|max:50',
            'ongkir'             => 'nullable|numeric|min:0',
        ]);

        $user = $request->user();
        $ongkir = (float) ($data['ongkir'] ?? 0);

        $produk = Product::whereIn('id', collect($data['items'])->pluck('product_id'))
            ->where('is_active', true)->get()->keyBy('id');

        // Semua item harus dari satu UMKM
        $umkmIds = $produk->pluck('umkm_id')->unique();
        if ($umkmIds->count() > 1) {
            return response()->json(['message' => 'Checkout hanya untuk satu UMKM per transaksi.'], 422);
        }
        $umkmId = $umkmIds->first();

        $baris = [];
        $total = 0;
        foreach ($data['items'] as $it) {
            $p = $produk[$it['product_id']] ?? null;
            if (! $p) {
                return response()->json(['message' => 'Produk tidak tersedia.'], 422);
            }
            if ($p->stok < $it['qty']) {
                return response()->json(['message' => "Stok {$p->nama} tidak mencukupi (tersisa {$p->stok})."], 422);
            }
            $subtotal = (float) $p->harga * $it['qty'];
            $baris[] = ['produk' => $p, 'qty' => $it['qty'], 'subtotal' => $subtotal];
            $total += $subtotal;
        }

        $grand = $total + $ongkir;

        if ($data['metode_bayar'] === 'saldo' && $this->wallet->saldo($user) < $grand) {
            return response()->json(['message' => 'Saldo tidak mencukupi untuk checkout.'], 422);
        }

        try {
            $order = DB::transaction(function () use ($data, $user, $umkmId, $baris, $total, $ongkir) {
                $order = Order::create([
                    'user_id'        => $user->id,
                    'umkm_id'        => $umkmId,
                    'total'          => $total,
                    'ongkir'         => $ongkir,
                    'metode_bayar'   => $data['metode_bayar'],
                    'status'         => $data['metode_bayar'] === 'saldo' ? 'dibayar' : 'menunggu_bayar',
                    'alamat_kirim'   => $data['alamat_kirim'],
                    'destination_id' => $data['destination_id'] ?? null,
                    'kurir'          => $data['kurir'] ?? null,
                ]);

                foreach ($baris as $b) {
                    $order->items()->create([
                        'product_id'     => $b['produk']->id,
                        'nama_snapshot'  => $b['produk']->nama,
                        'harga_snapshot' => $b['produk']->harga,
                        'qty'            => $b['qty'],
                        'subtotal'       => $b['subtotal'],
                    ]);
                    $b['produk']->decrement('stok', $b['qty']);
                }

                if ($data['metode_bayar'] === 'saldo') {
                    $this->wallet->debit($user, $total + $ongkir, 'belanja', $order, 'Belanja pesanan #' . $order->id);
                }

                return $order;
            });
        } catch (InsufficientBalanceException $e) {
            return response()->json(['message' => 'Saldo tidak mencukupi.'], 422);
        }

        $order->load('items', 'umkm');
        $payload = ['pesanan' => $this->orderPayload($order)];

        // Midtrans: buat snap token
        if ($data['metode_bayar'] === 'midtrans') {
            try {
                $items = $order->items->map(fn ($i) => [
                    'id'       => (string) $i->product_id,
                    'price'    => (int) round($i->harga_snapshot),
                    'quantity' => $i->qty,
                    'name'     => mb_substr($i->nama_snapshot, 0, 50),
                ])->values()->all();

                if ($ongkir > 0) {
                    $items[] = ['id' => 'ONGKIR', 'price' => (int) round($ongkir), 'quantity' => 1, 'name' => 'Ongkos Kirim'];
                }

                $snapToken = $midtrans->createSnapToken(
                    'ORDER-' . $order->id . '-' . now()->timestamp,
                    (int) round($total + $ongkir),
                    [
                        'first_name' => $user->name,
                        'email'      => $user->email ?: ('user' . $user->id . '@nitiresik.id'),
                        'phone'      => $user->phone,
                    ],
                    $items,
                );
                $payload['snap_token'] = $snapToken;
            } catch (\Throwable $e) {
                return response()->json(['message' => 'Gagal membuat pembayaran Midtrans. Coba lagi.'], 503);
            }
        }

        return response()->json(['message' => 'Checkout berhasil.', 'data' => $payload], 201);
    }

    /** Callback Midtrans (publik, tanpa auth). */
    public function notifikasiMidtrans(Request $request, MidtransService $midtrans)
    {
        try {
            $res = $midtrans->handleNotification($request->all());
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Signature tidak valid.'], 403);
        }

        // order_id berpola PREFIX-{id}-{timestamp}
        [$prefix, $id] = array_pad(explode('-', $res['order_id']), 2, null);

        if ($prefix === 'ORDER') {
            $this->handleOrderPayment($id, $res['status']);
        } elseif ($prefix === 'TPSSUB') {
            $this->handleTpsSubPayment($id, $res['status']);
        }

        return response()->json(['message' => 'ok']);
    }

    private function handleOrderPayment(?string $id, string $status): void
    {
        $order = $id ? Order::find($id) : null;
        if (! $order || $order->status !== 'menunggu_bayar') {
            return;
        }
        if ($status === 'paid') {
            $order->update(['status' => 'dibayar']);
        } elseif ($status === 'failed') {
            DB::transaction(function () use ($order) {
                $order->load('items');
                foreach ($order->items as $it) {
                    if ($it->product_id) {
                        Product::where('id', $it->product_id)->increment('stok', $it->qty);
                    }
                }
                $order->update(['status' => 'dibatalkan']);
            });
        }
    }

    private function handleTpsSubPayment(?string $id, string $status): void
    {
        $sub = $id ? TpsSubscription::with('member')->find($id) : null;
        if (! $sub || $sub->status === 'lunas') {
            return;
        }
        if ($status === 'paid') {
            $sub->update(['status' => 'lunas', 'paid_at' => now()]);
            $sub->member?->update(['status' => 'aktif']);
        } elseif ($status === 'failed') {
            $sub->update(['status' => 'gagal']);
        }
    }

    public function index(Request $request)
    {
        $q = Order::where('user_id', $request->user()->id)->with('umkm')->withCount('items')->latest();

        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }

        $data = $q->paginate(12)->through(fn ($o) => [
            'id' => $o->id, 'umkm' => $o->umkm?->nama, 'total' => (float) $o->total,
            'ongkir' => (float) $o->ongkir, 'status' => $o->status, 'jumlah_item' => $o->items_count,
            'no_resi' => $o->no_resi, 'kurir' => $o->kurir,
            'tanggal' => $o->created_at?->toIso8601String(),
        ]);

        return response()->json($data);
    }

    public function show(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        $order->load('items', 'umkm');

        return response()->json(['data' => $this->orderPayload($order)]);
    }

    public function cancel(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        if (! in_array($order->status, ['menunggu_bayar', 'dibayar'])) {
            return response()->json(['message' => 'Pesanan tidak dapat dibatalkan pada status ini.'], 422);
        }

        DB::transaction(function () use ($order, $request) {
            $order->load('items');
            foreach ($order->items as $it) {
                if ($it->product_id) {
                    Product::where('id', $it->product_id)->increment('stok', $it->qty);
                }
            }
            if ($order->metode_bayar === 'saldo' && $order->status === 'dibayar') {
                $this->wallet->credit($request->user(), (float) $order->total + (float) $order->ongkir, 'refund', $order, 'Refund pesanan #' . $order->id);
            }
            $order->update(['status' => 'dibatalkan']);
        });

        return response()->json(['message' => 'Pesanan dibatalkan.']);
    }

    private function orderPayload(Order $order): array
    {
        return [
            'id' => $order->id, 'umkm' => $order->umkm?->nama, 'total' => (float) $order->total,
            'ongkir' => (float) $order->ongkir, 'grand_total' => (float) $order->total + (float) $order->ongkir,
            'metode_bayar' => $order->metode_bayar, 'status' => $order->status,
            'alamat_kirim' => $order->alamat_kirim, 'kurir' => $order->kurir, 'no_resi' => $order->no_resi,
            'tanggal' => $order->created_at?->toIso8601String(),
            'items' => $order->items->map(fn ($i) => [
                'nama' => $i->nama_snapshot, 'harga' => (float) $i->harga_snapshot,
                'qty' => $i->qty, 'subtotal' => (float) $i->subtotal,
            ])->values(),
        ];
    }
}