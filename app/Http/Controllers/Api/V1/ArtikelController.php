<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArtikelResource;
use App\Http\Resources\ArtikelRingkasResource;
use App\Models\Artikel;
use App\Models\ArtikelKategori;
use App\Services\Konten\TeksBacaService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArtikelController extends Controller
{
    public function __construct(
        private readonly TeksBacaService $teksBaca,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Artikel::query()
            ->terbit()
            ->with('kategori:id,nama,slug')
            ->latest('published_at');

        if ($request->filled('kategori')) {
            $query->whereHas('kategori', fn ($q) => $q->where('slug', $request->string('kategori')->toString()));
        }

        if ($request->filled('tipe')) {
            $query->where('tipe', $request->string('tipe')->toString());
        }

        if ($request->boolean('unggulan')) {
            $query->unggulan();
        }

        if ($request->filled('cari')) {
            $query->where('judul', 'like', '%'.$request->string('cari')->toString().'%');
        }

        return ApiResponse::halaman(
            ArtikelRingkasResource::collection($query->paginate($request->integer('per_page', 12))),
        );
    }

    public function kategori(): JsonResponse
    {
        return ApiResponse::sukses(
            ArtikelKategori::query()
                ->withCount(['artikel' => fn ($q) => $q->terbit()])
                ->orderBy('nama')
                ->get()
                ->map(fn (ArtikelKategori $k): array => [
                    'id' => $k->id,
                    'nama' => $k->nama,
                    'slug' => $k->slug,
                    'jumlah_artikel' => $k->artikel_count,
                ])
                ->all(),
        );
    }

    public function show(Artikel $artikel): JsonResponse
    {
        $this->authorize('view', $artikel);

        $artikel->catatDilihat();

        return ApiResponse::resource(
            new ArtikelResource($artikel->load(['kategori', 'penulis'])),
        );
    }

    /**
     * Teks bersih markdown untuk pemutar suara.
     *
     * Endpoint terpisah karena isinya bisa sepanjang seluruh artikel dan
     * hanya dibutuhkan ketika TTS benar-benar dinyalakan. Web dan mobile
     * memanggil endpoint yang sama, sehingga keduanya membacakan kalimat
     * yang persis sama.
     */
    public function teksBaca(Artikel $artikel): JsonResponse
    {
        $this->authorize('view', $artikel);

        $teks = $this->teksBaca->untukArtikel($artikel);

        $artikel->catatDidengarkan();

        return ApiResponse::sukses([
            'judul' => $artikel->judul,
            'teks_baca' => $teks,
            'estimasi_baca_menit' => $artikel->estimasi_baca_menit,
            // Bahasa untuk speechSynthesis di klien.
            'bahasa' => 'id-ID',
        ]);
    }
}
