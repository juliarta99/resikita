<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BankSampahHargaResource;
use App\Http\Resources\BankSampahResource;
use App\Http\Resources\TpsResource;
use App\Http\Resources\UmkmResource;
use App\Http\Resources\WilayahResource;
use App\Models\BankSampah;
use App\Models\BankSampahHarga;
use App\Models\Tps;
use App\Models\Umkm;
use App\Models\Wilayah;
use App\Support\ApiResponse;
use App\Support\Haversine;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Direktori publik: TPS, bank sampah, UMKM, dan wilayah.
 *
 * Seluruhnya terbuka tanpa token. Warga harus bisa menemukan bank
 * sampah terdekat sebelum memutuskan mendaftar, mewajibkan akun lebih
 * dulu akan membalik urutan yang masuk akal.
 *
 * ## Relasi dimuat utuh, bukan beberapa kolom
 *
 * Setiap relasi di berkas ini dirender oleh API Resource-nya sendiri,
 * `wilayah` lewat WilayahResource, `bankSampah` lewat
 * BankSampahResource, dan keduanya membaca kolom bertipe enum
 * (`status_registrasi`, `status`). Kolom yang tidak ikut terpilih
 * bernilai null, lalu `null->value` melempar galat dan seluruh endpoint
 * mati. Keduanya tabel rujukan berukuran kecil; mempersempit kolomnya
 * tidak sepadan dengan risikonya.
 */
class DirektoriController extends Controller
{
    public function tps(Request $request): JsonResponse
    {
        $query = Tps::query()->with('wilayah')->withCount('anggota');

        $this->terapkanFilterUmum($query, $request);

        if ($request->filled('jenis')) {
            $query->where('jenis', $request->string('jenis')->toString());
        }

        return ApiResponse::halaman(
            TpsResource::collection($query->paginate($request->integer('per_page', 15))),
        );
    }

    public function tpsDetail(Tps $tps): JsonResponse
    {
        return ApiResponse::resource(
            new TpsResource($tps->load('wilayah.parent')->loadCount('anggota')),
        );
    }

    public function bankSampah(Request $request): JsonResponse
    {
        $query = BankSampah::query()
            ->aktif()
            ->with('wilayah')
            ->withCount('harga');

        $this->terapkanFilterUmum($query, $request);

        return ApiResponse::halaman(
            BankSampahResource::collection($query->paginate($request->integer('per_page', 15))),
        );
    }

    public function bankSampahDetail(BankSampah $bankSampah): JsonResponse
    {
        return ApiResponse::sukses([
            'bank_sampah' => (new BankSampahResource($bankSampah->load('wilayah.parent')))->resolve(),
            'harga' => BankSampahHargaResource::collection(
                $bankSampah->harga()->aktif()->orderBy('jenis_sampah')->get(),
            )->resolve(),
        ]);
    }

    public function umkm(Request $request): JsonResponse
    {
        $query = Umkm::query()
            ->aktif()
            ->with('wilayah')
            ->withCount(['produk', 'ulasan'])
            ->withAvg('ulasan as rating_rata', 'rating');

        $this->terapkanFilterUmum($query, $request);

        return ApiResponse::halaman(
            UmkmResource::collection($query->paginate($request->integer('per_page', 15))),
        );
    }

    public function umkmDetail(Umkm $umkm): JsonResponse
    {
        return ApiResponse::resource(
            new UmkmResource(
                $umkm->load('wilayah.parent')
                    ->loadCount(['produk', 'ulasan'])
                    ->loadAvg('ulasan as rating_rata', 'rating'),
            ),
        );
    }

    /**
     * Pencarian wilayah untuk pemilih alamat dan pengajuan wilayah.
     */
    public function wilayah(Request $request): JsonResponse
    {
        /*
         * Induk dimuat utuh, bukan tiga kolom saja. WilayahResource
         * dipakai ulang untuk induk, dan ia membaca status registrasi
         * serta koordinat, kolom yang hilang berubah menjadi galat 500
         * pada permintaan yang di luar itu sepenuhnya sah.
         */
        $query = Wilayah::query()->with('parent');

        if ($request->filled('tingkat')) {
            $query->where('tingkat', $request->string('tingkat')->toString());
        }

        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->integer('parent_id'));
        }

        if ($request->filled('cari')) {
            $cari = $request->string('cari')->toString();
            $query->where(fn ($q) => $q->where('nama', 'like', "%$cari%")->orWhere('kode', 'like', "$cari%"));
        }

        return ApiResponse::halaman(
            WilayahResource::collection(
                $query->orderBy('nama')->paginate($request->integer('per_page', 30)),
            ),
        );
    }

    /**
     * Katalog harga sampah lintas bank sampah, disaring wilayah.
     *
     * Berguna untuk warga yang ingin membandingkan sebelum memilih ke
     * mana menyetor.
     */
    public function hargaSampah(Request $request): JsonResponse
    {
        $query = BankSampahHarga::query()
            ->aktif()
            ->with('bankSampah')
            ->whereHas('bankSampah', fn ($q) => $q->aktif());

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->string('kategori')->toString());
        }

        if ($request->filled('wilayah_id')) {
            $query->whereHas('bankSampah', fn ($q) => $q->where('wilayah_id', $request->integer('wilayah_id')));
        }

        return ApiResponse::halaman(
            BankSampahHargaResource::collection(
                $query->orderBy('jenis_sampah')->paginate($request->integer('per_page', 30)),
            ),
        );
    }

    /**
     * Filter yang berlaku sama untuk TPS, bank sampah, dan UMKM:
     * pencarian nama, penyaringan wilayah, dan urutan terdekat.
     *
     * @param  Builder<Model>  $query
     */
    private function terapkanFilterUmum($query, Request $request): void
    {
        if ($request->filled('cari')) {
            $query->where('nama', 'like', '%'.$request->string('cari')->toString().'%');
        }

        if ($request->filled('wilayah_id')) {
            $query->where('wilayah_id', $request->integer('wilayah_id'));
        }

        // Urut terdekat kalau klien mengirim posisinya. Jarak dihitung di
        // basis data, bukan setelah seluruh baris ditarik ke PHP.
        if ($request->filled(['latitude', 'longitude'])) {
            Haversine::terapkan(
                $query,
                $request->float('latitude'),
                $request->float('longitude'),
                $request->filled('radius_km') ? $request->float('radius_km') : null,
            );
        }
    }
}
