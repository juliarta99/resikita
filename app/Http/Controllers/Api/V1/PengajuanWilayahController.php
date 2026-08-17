<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wilayah\AjukanWilayahRequest;
use App\Http\Resources\PengajuanWilayahResource;
use App\Models\PengajuanWilayah;
use App\Models\Wilayah;
use App\Services\Wilayah\PengajuanWilayahService;
use App\Support\ApiResponse;
use App\Support\Unggahan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Pendaftaran wilayah ke Resikita.
 *
 * `ajukan()` sengaja terbuka tanpa token: pemohonnya pejabat daerah
 * yang belum punya akun Resikita, dan mensyaratkan login lebih dulu
 * akan membuat fitur ini mustahil dipakai orang yang justru dituju.
 * Yang menjaga mutunya adalah surat bukti kewenangan dan peninjauan
 * super admin, bukan autentikasi.
 */
class PengajuanWilayahController extends Controller
{
    public function __construct(
        private readonly PengajuanWilayahService $pengajuan,
    ) {}

    public function ajukan(AjukanWilayahRequest $request): JsonResponse
    {
        $wilayah = Wilayah::findOrFail($request->integer('wilayah_id'));

        $data = $request->safe()->except('surat', 'wilayah_id');
        $data['surat_path'] = Unggahan::simpan($request->file('surat'), 'pengajuan-wilayah', 'local');

        $pengajuan = $this->pengajuan->ajukan($wilayah, $data);

        return ApiResponse::dibuat(
            (new PengajuanWilayahResource($pengajuan->load('wilayah')))->resolve(),
            'Pengajuan diterima. Tim Resikita akan meninjau berkas Anda dan menghubungi lewat email yang didaftarkan.',
        );
    }

    /**
     * Lacak status pengajuan lewat email pemohon.
     *
     * Pemohon belum punya akun, jadi tidak ada cara lain untuk mengecek
     * kecuali lewat email yang ia daftarkan sendiri.
     */
    public function lacak(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email:rfc']]);

        $pengajuan = PengajuanWilayah::query()
            ->where('pemohon_email', $request->string('email')->toString())
            ->with('wilayah')
            ->latest('id')
            ->get();

        return ApiResponse::sukses(
            PengajuanWilayahResource::collection($pengajuan)->resolve(),
        );
    }
}
