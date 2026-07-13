<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WasteDeposit;
use App\Models\Withdrawal;
use App\Services\Domain\WalletService;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(private WalletService $wallet)
    {
    }

    public function saldo(Request $request)
    {
        return response()->json(['data' => ['saldo' => (float) $this->wallet->saldo($request->user())]]);
    }

    public function transaksi(Request $request)
    {
        $wallet = $this->wallet->walletFor($request->user());

        $trx = $wallet->transactions()->latest()->paginate(20)
            ->through(fn ($t) => [
                'id'          => $t->id,
                'tipe'        => $t->tipe,
                'jumlah'      => (float) $t->jumlah,
                'saldo_after' => (float) $t->saldo_after,
                'keterangan'  => $t->keterangan,
                'tanggal'     => $t->created_at?->toIso8601String(),
            ]);

        return response()->json($trx);
    }

    /** Riwayat setor sampah (sebagai nasabah). */
    public function setoran(Request $request)
    {
        $data = WasteDeposit::with(['bankSampah', 'items.wastePrice'])
            ->where('nasabah_id', $request->user()->id)
            ->latest()->paginate(20)
            ->through(fn ($d) => [
                'id'          => $d->id,
                'bank_sampah' => $d->bankSampah?->nama,
                'total_berat' => (float) $d->total_berat,
                'total_nilai' => (float) $d->total_nilai,
                'tanggal'     => $d->created_at?->toIso8601String(),
                'rincian'     => $d->items->map(fn ($i) => [
                    'jenis'    => $i->wastePrice?->jenis_sampah,
                    'berat'    => (float) $i->berat,
                    'subtotal' => (float) $i->subtotal,
                ])->values(),
            ]);

        return response()->json($data);
    }

    /** Ajukan penarikan saldo. */
    public function ajukanPenarikan(Request $request)
    {
        $data = $request->validate([
            'jumlah'      => 'required|numeric|min:10000',
            'metode'      => 'required|string|max:50',   // mis. transfer_bank / ewallet
            'no_rekening' => 'required|string|max:100',
        ]);

        $user = $request->user();
        $saldo = (float) $this->wallet->saldo($user);

        // Saldo yang sudah "dikunci" oleh penarikan yang belum tuntas
        $tertahan = (float) Withdrawal::where('user_id', $user->id)
            ->whereIn('status', ['menunggu', 'disetujui'])->sum('jumlah');

        if ($data['jumlah'] > ($saldo - $tertahan)) {
            return response()->json([
                'message' => 'Saldo tersedia tidak mencukupi (memperhitungkan penarikan yang masih diproses).',
            ], 422);
        }

        $w = Withdrawal::create([
            'user_id'     => $user->id,
            'jumlah'      => $data['jumlah'],
            'metode'      => $data['metode'],
            'no_rekening' => $data['no_rekening'],
            'status'      => 'menunggu',
        ]);

        return response()->json([
            'message' => 'Permintaan penarikan diajukan. Menunggu persetujuan admin.',
            'data'    => ['id' => $w->id, 'jumlah' => (float) $w->jumlah, 'status' => $w->status],
        ], 201);
    }

    public function penarikan(Request $request)
    {
        $data = Withdrawal::where('user_id', $request->user()->id)->latest()->paginate(20)
            ->through(fn ($w) => [
                'id'          => $w->id,
                'jumlah'      => (float) $w->jumlah,
                'metode'      => $w->metode,
                'no_rekening' => $w->no_rekening,
                'status'      => $w->status,
                'catatan'     => $w->catatan,
                'tanggal'     => $w->created_at?->toIso8601String(),
            ]);

        return response()->json($data);
    }
}