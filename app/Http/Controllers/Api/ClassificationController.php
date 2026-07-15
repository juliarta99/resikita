<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classification;
use App\Services\Integration\GeminiService;
use Illuminate\Http\Request;

class ClassificationController extends Controller
{
    private const KATEGORI_LABEL = [
        'organik'     => 'Organik',
        'anorganik'   => 'Anorganik',
        'b3'          => 'B3',
        'residu'      => 'Residu',
        'tidak_yakin' => 'Tidak Yakin',
    ];

    /** Klasifikasikan gambar + simpan ke riwayat. */
    public function store(Request $request, GeminiService $gemini)
    {
        $request->validate(['gambar' => 'required|image|max:6144']);

        try {
            $hasil = $gemini->classifyWaste($request->file('gambar')->getRealPath());
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Klasifikasi gagal. Pastikan foto jelas & pencahayaan cukup, lalu coba lagi.',
            ], 503);
        }

        $foto = $request->file('gambar')->store('classifications', 'public');

        $item = Classification::create([
            'user_id'            => $request->user()->id,
            'foto'               => $foto,
            'hasil_jenis'        => $hasil['hasil_jenis'] ?? 'Sampah',
            'deskripsi'          => $hasil['deskripsi'] ?? null,
            'kategori'           => $hasil['kategori'] ?? 'tidak_yakin',
            'material'           => $hasil['material'] ?? null,
            'confidence'         => min(1, max(0, (float) ($hasil['confidence'] ?? 0))),
            'dapat_didaur_ulang' => (bool) ($hasil['dapat_disetor_bank_sampah'] ?? false),
            'nilai_jual'         => (float) ($hasil['nilai_jual_per_kg'] ?? 0),
            'estimasi_berat'     => (float) ($hasil['estimasi_berat_kg'] ?? 0),
            'langkah_pengolahan' => $hasil['langkah_pengolahan'] ?? [],
            'rekomendasi'        => $hasil['rekomendasi_daur_ulang'] ?? null,
            'catatan'            => $hasil['catatan'] ?? null,
        ]);

        return response()->json(['data' => $this->payload($item)], 201);
    }

    /** Riwayat klasifikasi milik pengguna. ?kategori=anorganik & ?q=botol */
    public function index(Request $request)
    {
        $q = Classification::where('user_id', $request->user()->id)->latest();

        if ($kategori = $request->query('kategori')) {
            $q->where('kategori', $kategori);
        }
        if ($cari = $request->query('q')) {
            $q->where(fn ($w) => $w->where('hasil_jenis', 'like', "%{$cari}%")->orWhere('material', 'like', "%{$cari}%"));
        }

        return response()->json([
            'data' => $q->get()->map(fn ($c) => $this->payload($c, true))->values(),
        ]);
    }

    public function show(Request $request, Classification $classification)
    {
        abort_unless($classification->user_id === $request->user()->id, 403);

        return response()->json(['data' => $this->payload($classification)]);
    }

    public function destroy(Request $request, Classification $classification)
    {
        abort_unless($classification->user_id === $request->user()->id, 403);
        $classification->delete();

        return response()->json(['message' => 'Riwayat dihapus.']);
    }

    /** Bentuk payload konsisten untuk FE. */
    private function payload(Classification $c, bool $ringkas = false): array
    {
        $base = [
            'id'             => $c->id,
            'foto'           => $c->foto ? asset('storage/' . $c->foto) : null,
            'hasil_jenis'    => $c->hasil_jenis,
            'kategori'       => $c->kategori,
            'kategori_label' => self::KATEGORI_LABEL[$c->kategori] ?? ucfirst($c->kategori),
            'material'       => $c->material,
            'akurasi_persen' => (int) round($c->confidence * 100),
            'nilai_jual'     => (float) $c->nilai_jual,
            'tanggal'        => $c->created_at?->toIso8601String(),
        ];

        if ($ringkas) {
            return $base;
        }

        return array_merge($base, [
            'deskripsi'          => $c->deskripsi,
            'confidence'         => (float) $c->confidence,
            'dapat_didaur_ulang' => (bool) $c->dapat_didaur_ulang,
            'estimasi_berat'     => (float) $c->estimasi_berat,
            'langkah_pengolahan' => $c->langkah_pengolahan ?? [],
            'rekomendasi'        => $c->rekomendasi,
            'catatan'            => $c->catatan,
        ]);
    }
}