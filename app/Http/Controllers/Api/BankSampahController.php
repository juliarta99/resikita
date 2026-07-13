<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WasteDeposit;
use App\Models\WastePrice;
use App\Services\Domain\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BankSampahController extends Controller
{
    public function __construct(private WalletService $wallet)
    {
    }

    /** Daftar harga sampah aktif (untuk form setor). */
    public function harga()
    {
        $data = WastePrice::where('is_active', true)->orderBy('jenis_sampah')->get()
            ->map(fn ($h) => [
                'id' => $h->id, 'jenis_sampah' => $h->jenis_sampah,
                'satuan' => $h->satuan, 'harga_per_kg' => (float) $h->harga_per_kg,
            ]);

        return response()->json(['data' => $data]);
    }

    /** Cari nasabah dari QR (kode_qr) atau NIK. */
    public function cariNasabah(Request $request)
    {
        $request->validate(['kode' => 'required|string']);
        $kode = trim($request->query('kode', $request->input('kode')));

        $nasabah = User::role('masyarakat')
            ->where(fn ($q) => $q->where('kode_qr', $kode)->orWhere('nik', $kode))
            ->first();

        if (! $nasabah) {
            return response()->json(['message' => 'Nasabah tidak ditemukan untuk kode/QR tersebut.'], 404);
        }

        return response()->json(['data' => [
            'id' => $nasabah->id, 'nama' => $nasabah->name,
            'kode_qr' => $nasabah->kode_qr,
            'saldo' => (float) $this->wallet->saldo($nasabah),
        ]]);
    }

    public function setor(Request $request)
    {
        $data = $request->validate([
            'nasabah_id'             => 'required|integer',
            'items'                  => 'required|array|min:1',
            'items.*.waste_price_id' => 'required|exists:waste_prices,id',
            'items.*.berat'          => 'required|numeric|gt:0',
        ], [], [
            'items.*.waste_price_id' => 'jenis sampah',
            'items.*.berat'          => 'berat',
        ]);

        $petugas = $request->user();
        if (! $petugas->bank_sampah_id) {
            return response()->json(['message' => 'Akun Anda belum terhubung ke bank sampah.'], 422);
        }

        $nasabah = User::role('masyarakat')->find($data['nasabah_id']);
        if (! $nasabah) {
            return response()->json(['message' => 'Nasabah tidak valid.'], 404);
        }

        $prices = WastePrice::whereIn('id', collect($data['items'])->pluck('waste_price_id'))->get()->keyBy('id');

        $rows = [];
        $totalBerat = 0;
        $totalNilai = 0;
        foreach ($data['items'] as $it) {
            $price = $prices[$it['waste_price_id']] ?? null;
            $berat = (float) $it['berat'];
            if (! $price || $berat <= 0) {
                continue;
            }
            $subtotal = round($berat * (float) $price->harga_per_kg, 2);
            $rows[] = [
                'waste_price_id' => $price->id,
                'berat'          => $berat,
                'harga_snapshot' => $price->harga_per_kg,
                'subtotal'       => $subtotal,
            ];
            $totalBerat += $berat;
            $totalNilai += $subtotal;
        }

        if (empty($rows)) {
            return response()->json(['message' => 'Rincian setoran belum valid.'], 422);
        }

        $deposit = DB::transaction(function () use ($petugas, $nasabah, $rows, $totalBerat, $totalNilai) {
            $deposit = WasteDeposit::create([
                'bank_sampah_id' => $petugas->bank_sampah_id,
                'petugas_id'     => $petugas->id,
                'nasabah_id'     => $nasabah->id,
                'total_berat'    => $totalBerat,
                'total_nilai'    => $totalNilai,
                'status'         => 'selesai',
            ]);
            $deposit->items()->createMany($rows);

            $this->wallet->credit(
                $nasabah, $totalNilai, 'setor', $deposit,
                'Setor sampah di ' . ($petugas->bankSampah?->nama ?? 'bank sampah')
            );

            return $deposit;
        });

        return response()->json([
            'message' => 'Setoran berhasil dicatat.',
            'data'    => [
                'deposit_id'  => $deposit->id,
                'total_berat' => (float) $totalBerat,
                'total_nilai' => (float) $totalNilai,
                'nasabah'     => $nasabah->name,
                'saldo_baru'  => (float) $this->wallet->saldo($nasabah->fresh()),
            ],
        ], 201);
    }

    public function riwayat(Request $request)
    {
        $petugas = $request->user();
        if (! $petugas->bank_sampah_id) {
            return response()->json(['data' => []]);
        }

        $q = WasteDeposit::with('nasabah')
            ->where('bank_sampah_id', $petugas->bank_sampah_id)
            ->latest();

        if ($request->boolean('hari_ini')) {
            $q->whereDate('created_at', today());
        }

        $data = $q->paginate(20)->through(fn ($d) => [
            'id' => $d->id, 'nasabah' => $d->nasabah?->name,
            'total_berat' => (float) $d->total_berat, 'total_nilai' => (float) $d->total_nilai,
            'tanggal' => $d->created_at?->toIso8601String(),
        ]);

        return response()->json($data);
    }
}