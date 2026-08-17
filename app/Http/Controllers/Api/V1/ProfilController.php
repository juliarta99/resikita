<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\GantiPasswordRequest;
use App\Http\Requests\Auth\PerbaruiProfilRequest;
use App\Http\Resources\UserResource;
use App\Services\Auth\AkunService;
use App\Services\Auth\AuthService;
use App\Support\ApiResponse;
use App\Support\Unggahan;
use Illuminate\Http\JsonResponse;

class ProfilController extends Controller
{
    public function __construct(
        private readonly AkunService $akun,
        private readonly AuthService $auth,
    ) {}

    public function perbarui(PerbaruiProfilRequest $request): JsonResponse
    {
        $data = $request->safe()->except('avatar');

        if ($request->hasFile('avatar')) {
            $data['avatar_path'] = Unggahan::simpan($request->file('avatar'), 'avatar');
        }

        $user = $this->akun->perbarui($request->user(), $data);

        return ApiResponse::resource(
            new UserResource($user->load('wilayah')),
            'Profil berhasil diperbarui.',
        );
    }

    public function gantiPassword(GantiPasswordRequest $request): JsonResponse
    {
        $this->auth->gantiPassword(
            $request->user(),
            $request->string('password_lama')->toString(),
            $request->string('password')->toString(),
        );

        return ApiResponse::kosong('Kata sandi berhasil diubah.');
    }
}
