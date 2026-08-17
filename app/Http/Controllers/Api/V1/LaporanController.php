<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Laporan\CekDuplikatRequest;
use App\Http\Requests\Laporan\SimpanLaporanRequest;
use App\Http\Resources\LaporanKategoriResource;
use App\Http\Resources\LaporanResource;
use App\Http\Resources\LaporanRingkasResource;
use App\Models\Laporan;
use App\Models\LaporanKategori;
use App\Services\Laporan\LaporanService;
use App\Services\Wilayah\WilayahScopeService;
use App\Support\ApiResponse;
use App\Support\Unggahan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LaporanController extends Controller
{
    public function __construct(
        private readonly LaporanService $laporan,
        private readonly WilayahScopeService $scope,
    ) {}

    public function kategori(): JsonResponse
    {
        return ApiResponse::sukses(
            LaporanKategoriResource::collection(
                LaporanKategori::query()->aktif()->orderBy('nama')->get(),
            )->resolve(),
        );
    }

    /**
     * Daftar laporan sesuai cakupan pengguna.
     *
     * Pembatasannya lewat WilayahScopeService, bukan filter manual,
     * warga melihat laporannya sendiri, pemerintah melihat wilayahnya,
     * petugas melihat penugasannya.
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->scope
            ->applyLaporan(Laporan::query(), $request->user())
            ->untukDaftar()
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('cari')) {
            $cari = $request->string('cari')->toString();
            $query->where(fn ($q) => $q
                ->where('judul', 'like', "%$cari%")
                ->orWhere('tiket', 'like', "%$cari%"));
        }

        return ApiResponse::halaman(
            LaporanRingkasResource::collection(
                $query->paginate($request->integer('per_page', 15)),
            ),
        );
    }

    public function show(Laporan $laporan): JsonResponse
    {
        $this->authorize('view', $laporan);

        $laporan->load([
            'kategori', 'pelapor', 'foto', 'desa', 'kecamatan', 'kabupaten', 'provinsi',
            'progres.petugas', 'penugasan.petugas',
        ])->loadCount('duplikat');

        return ApiResponse::resource(new LaporanResource($laporan));
    }

    /**
     * Cari laporan kembar sebelum menyimpan.
     *
     * Dipanggil terpisah dari store() supaya aplikasi bisa menampilkan
     * kandidat dan membiarkan warga memilih: gabungkan, atau kirim
     * sebagai laporan tersendiri. Sistem tidak memutuskan sendiri, dan
     * tidak pernah menolak laporan kedua.
     */
    public function cekDuplikat(CekDuplikatRequest $request): JsonResponse
    {
        $kandidat = $this->laporan->cekDuplikat(
            $request->float('latitude'),
            $request->float('longitude'),
            $request->integer('kategori_id') ?: null,
        );

        return ApiResponse::sukses(
            [
                'ada_kandidat' => $kandidat->isNotEmpty(),
                'radius_meter' => (int) config('resikita.laporan.radius_duplikat_m'),
                'kandidat' => LaporanRingkasResource::collection($kandidat)->resolve(),
            ],
            $kandidat->isNotEmpty()
                ? 'Ada laporan serupa di sekitar titik ini. Anda bisa menggabungkan laporan agar penanganannya terpusat.'
                : null,
        );
    }

    public function store(SimpanLaporanRequest $request): JsonResponse
    {
        $this->authorize('create', Laporan::class);

        // Gagal pada satu foto membatalkan seluruhnya dan membuang yang
        // telanjur tersimpan. Laporan dengan tiga foto yang hanya dua di
        // antaranya masuk lebih menyesatkan daripada laporan yang jelas
        // gagal dikirim.
        $fotoPaths = Unggahan::simpanBanyak($request->file('foto', []), 'laporan');

        $laporan = $this->laporan->buat(
            $request->user(),
            $request->safe()->except('foto', 'gabung_ke_id'),
            $fotoPaths,
            $request->integer('gabung_ke_id') ?: null,
        );

        return ApiResponse::dibuat(
            (new LaporanResource($laporan))->resolve(),
            "Laporan Anda tercatat dengan nomor tiket {$laporan->tiket}.",
        );
    }
}
