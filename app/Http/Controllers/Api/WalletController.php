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
        $user = $request->user();
        $saldo = (float) $this->wallet->saldo($user);

        $masukBulanIni = (float) WasteDeposit::where('nasabah_id', $user->id)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('total_nilai');

        $terakhir = $this->wallet->walletFor($user)
            ->transactions()->latest()->first()?->created_at;

        return response()->json(['data' => [
            'saldo'              => $saldo,
            'masuk_bulan_ini'    => $masukBulanIni,
            'transaksi_terakhir' => $terakhir?->toIso8601String(),
            'kode_qr'            => $user->kode_qr,
        ]]);
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
                'alamat'      => $d->bankSampah?->alamat,
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
            'metode'      => 'nullable|string|max:50',    // default transfer_bank
            'no_rekening' => 'required|string|max:100',
            'nama_bank'   => 'nullable|string|max:100',
            'atas_nama'   => 'nullable|string|max:100',
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
            'metode'      => $data['metode'] ?? 'transfer_bank',
            'no_rekening' => $data['no_rekening'],
            'nama_bank'   => $data['nama_bank'] ?? null,
            'atas_nama'   => $data['atas_nama'] ?? null,
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
                'nama_bank'   => $w->nama_bank,
                'atas_nama'   => $w->atas_nama,
                'status'      => $w->status,
                'catatan'     => $w->catatan,
                'tanggal'     => $w->created_at?->toIso8601String(),
            ]);

        return response()->json($data);
    }
}
