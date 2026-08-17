<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\TingkatWilayah;
use App\Http\Controllers\Controller;
use App\Http\Resources\WilayahResource;
use App\Models\Wilayah;
use App\Services\Wilayah\WilayahResolverService;
use App\Support\ApiResponse;
use App\Support\Haversine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Pemilih wilayah bertingkat dan resolusi koordinat.
 *
 * Terbuka tanpa token: formulir pengajuan wilayah dipakai pejabat daerah
 * yang justru belum punya akun, dan layar pendaftaran warga membutuhkan
 * pemilih domisili sebelum akunnya ada.
 *
 * **Jangan pernah memuat seluruh desa sekaligus.** Data lengkapnya
 * sekitar 84.000 baris. Pemilih dibuat bertingkat karena itu, bukan
 * karena selera antarmuka.
 */
class WilayahController extends Controller
{
    /** Daftar provinsi, tingkat teratas pemilih bertingkat. */
    public function provinsi(): JsonResponse
    {
        return ApiResponse::sukses(
            WilayahResource::collection(
                Wilayah::query()
                    ->tingkat(TingkatWilayah::Provinsi)
                    ->orderBy('nama')
                    ->get(),
            )->resolve(),
        );
    }

    /** Anak langsung dari satu wilayah. */
    public function anak(Wilayah $wilayah): JsonResponse
    {
        return ApiResponse::sukses(
            WilayahResource::collection(
                $wilayah->children()->orderBy('nama')->get(),
            )->resolve(),
        );
    }

    public function show(Wilayah $wilayah): JsonResponse
    {
        return ApiResponse::resource(
            new WilayahResource($wilayah->load('parent.parent')),
        );
    }

    /**
     * Ubah sepasang koordinat menjadi empat tingkat wilayah.
     *
     * Dipakai aplikasi ponsel untuk menunjukkan wilayah laporan sebelum
     * dikirim, sehingga pelapor bisa membetulkan titiknya kalau meleset.
     * Logikanya sama persis dengan yang dipakai saat laporan disimpan,
     * keduanya memanggil WilayahResolverService, jadi apa yang
     * diperlihatkan tidak mungkin berbeda dari apa yang tersimpan.
     */
    public function resolusi(Request $request, WilayahResolverService $resolver): JsonResponse
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $hasil = $resolver->resolve((float) $data['latitude'], (float) $data['longitude']);

        $muat = fn (?int $id): ?array => $id === null
            ? null
            : (new WilayahResource(Wilayah::find($id)))->resolve();

        return ApiResponse::sukses([
            'ditemukan' => ! $hasil->tidakDitemukan(),
            'jarak_km' => $hasil->jarakKm !== null ? round($hasil->jarakKm, 2) : null,
            'desa' => $muat($hasil->desaId),
            'kecamatan' => $muat($hasil->kecamatanId),
            'kabupaten' => $muat($hasil->kabupatenId),
            'provinsi' => $muat($hasil->provinsiId),
        ]);
    }

    /**
     * Pencarian lintas tingkat.
     *
     * Ketika seseorang mengetik nama desa, ia tidak tahu, dan tidak
     * perlu tahu, kabupaten mana yang harus dibuka lebih dulu.
     */
    public function cari(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:100'],
            'tingkat' => ['nullable', 'string'],
        ]);

        $kata = trim($data['q']);

        $query = Wilayah::query()
            ->with('parent')
            ->where(fn ($q) => $q->where('nama', 'like', "%{$kata}%")->orWhere('kode', 'like', "{$kata}%"))
            ->orderBy('nama')
            ->limit(30);

        if (! empty($data['tingkat'])) {
            $query->where('tingkat', $data['tingkat']);
        }

        return ApiResponse::sukses(
            WilayahResource::collection($query->get())->resolve(),
        );
    }

    /**
     * Wilayah terdekat dari sepasang koordinat.
     *
     * Berguna untuk mengisi domisili saat pendaftaran: aplikasi mengirim
     * posisi pengguna, peladen mengembalikan desa terdekat sebagai
     * usulan yang tetap bisa diubah.
     */
    public function terdekat(Request $request): JsonResponse
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'tingkat' => ['nullable', 'string'],
        ]);

        $query = Wilayah::query()->with('parent');

        if (! empty($data['tingkat'])) {
            $query->where('tingkat', $data['tingkat']);
        }

        Haversine::terapkan($query, (float) $data['latitude'], (float) $data['longitude']);

        return ApiResponse::sukses(
            WilayahResource::collection($query->limit(10)->get())->resolve(),
        );
    }
}
