<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\PlatformPerangkat;
use App\Http\Controllers\Controller;
use App\Http\Resources\NotifikasiResource;
use App\Models\Notifikasi;
use App\Models\PerangkatToken;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotifikasiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Notifikasi::query()
            ->where('user_id', $request->user()->id)
            ->latest('id');

        if ($request->boolean('belum_dibaca')) {
            $query->belumDibaca();
        }

        return ApiResponse::halaman(
            NotifikasiResource::collection($query->paginate($request->integer('per_page', 20))),
        );
    }

    public function jumlahBelumDibaca(Request $request): JsonResponse
    {
        return ApiResponse::sukses([
            'belum_dibaca' => Notifikasi::query()
                ->where('user_id', $request->user()->id)
                ->belumDibaca()
                ->count(),
        ]);
    }

    public function tandaiDibaca(Request $request, Notifikasi $notifikasi): JsonResponse
    {
        abort_if($notifikasi->user_id !== $request->user()->id, 403);

        $notifikasi->tandaiDibaca();

        return ApiResponse::resource(new NotifikasiResource($notifikasi->fresh()));
    }

    public function tandaiSemuaDibaca(Request $request): JsonResponse
    {
        Notifikasi::query()
            ->where('user_id', $request->user()->id)
            ->belumDibaca()
            ->update(['dibaca_at' => now(), 'status' => 'dibaca']);

        return ApiResponse::kosong('Semua notifikasi ditandai sudah dibaca.');
    }

    /**
     * Daftarkan token perangkat untuk notifikasi dorong.
     *
     * Token yang sama bisa berpindah pemilik ketika satu ponsel dipakai
     * bergantian, jadi pendaftaran memperbarui pemiliknya alih-alih
     * menolak karena token sudah ada.
     */
    public function daftarkanPerangkat(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:255'],
            'platform' => ['required', Rule::enum(PlatformPerangkat::class)],
        ]);

        PerangkatToken::updateOrCreate(
            ['token' => $data['token']],
            [
                'user_id' => $request->user()->id,
                'platform' => $data['platform'],
                'terakhir_aktif_at' => now(),
            ],
        );

        return ApiResponse::kosong('Perangkat terdaftar untuk notifikasi.');
    }

    public function hapusPerangkat(Request $request): JsonResponse
    {
        $request->validate(['token' => ['required', 'string']]);

        PerangkatToken::query()
            ->where('user_id', $request->user()->id)
            ->where('token', $request->string('token')->toString())
            ->delete();

        return ApiResponse::kosong('Perangkat dilepas dari notifikasi.');
    }
}
