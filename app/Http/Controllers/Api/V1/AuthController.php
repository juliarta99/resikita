<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\DaftarRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\VerifikasiOtpRequest;
use App\Http\Resources\UserResource;
use App\Services\Auth\AuthService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Pendaftaran, masuk, dan pemulihan akun untuk aplikasi mobile.
 *
 * Controller ini tidak memuat satu pun aturan: seluruh keputusan ada di
 * AuthService, yang juga dipakai jalur web. Yang berbeda hanya apa yang
 * dilakukan setelah kredensial diterima, di sini token Sanctum, di web
 * sesi.
 */
class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $auth,
    ) {}

    public function daftar(DaftarRequest $request): JsonResponse
    {
        $user = $this->auth->daftar($request->validated());

        return ApiResponse::dibuat(
            [
                'user' => (new UserResource($user->load('wilayah')))->resolve(),
                'token' => $this->auth->terbitkanToken($user, $request->input('nama_perangkat', 'mobile')),
            ],
            'Pendaftaran berhasil. Kode verifikasi sudah dikirim ke email Anda.',
        );
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = $this->auth->autentikasi(
            $request->string('email')->toString(),
            $request->string('password')->toString(),
        );

        return ApiResponse::sukses(
            [
                'user' => (new UserResource($user->load('wilayah')))->resolve(),
                'token' => $this->auth->terbitkanToken($user, $request->input('nama_perangkat', 'mobile')),
            ],
            'Selamat datang kembali.',
        );
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::resource(
            new UserResource($request->user()->load('wilayah')),
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $this->auth->cabutTokenSaatIni($request->user());

        return ApiResponse::kosong('Anda telah keluar.');
    }

    public function kirimVerifikasiEmail(Request $request): JsonResponse
    {
        $this->auth->kirimVerifikasiEmail($request->user());

        return ApiResponse::kosong('Kode verifikasi sudah dikirim ke email Anda.');
    }

    public function verifikasiEmail(VerifikasiOtpRequest $request): JsonResponse
    {
        $berhasil = $this->auth->verifikasiEmail(
            $request->user(),
            $request->string('kode')->toString(),
        );

        return $berhasil
            ? ApiResponse::kosong('Email berhasil diverifikasi.')
            : ApiResponse::gagal('Kode verifikasi salah atau sudah kedaluwarsa.', 422);
    }

    public function kirimVerifikasiPhone(Request $request): JsonResponse
    {
        $this->auth->kirimVerifikasiPhone($request->user());

        return ApiResponse::kosong('Kode verifikasi sudah dikirim ke WhatsApp Anda.');
    }

    public function verifikasiPhone(VerifikasiOtpRequest $request): JsonResponse
    {
        $berhasil = $this->auth->verifikasiPhone(
            $request->user(),
            $request->string('kode')->toString(),
        );

        return $berhasil
            ? ApiResponse::kosong('Nomor WhatsApp berhasil diverifikasi.')
            : ApiResponse::gagal('Kode verifikasi salah atau sudah kedaluwarsa.', 422);
    }

    /**
     * Selalu melapor berhasil, termasuk untuk email yang tidak terdaftar.
     * Membedakan keduanya akan mengubah endpoint ini menjadi alat
     * pemeriksa siapa saja yang punya akun di Resikita.
     */
    public function lupaPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email:rfc']]);

        $this->auth->mintaResetPassword($request->string('email')->toString());

        return ApiResponse::kosong(
            'Jika email tersebut terdaftar, kode pemulihan sudah kami kirimkan.',
        );
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $berhasil = $this->auth->resetPassword(
            $request->string('email')->toString(),
            $request->string('kode')->toString(),
            $request->string('password')->toString(),
        );

        return $berhasil
            ? ApiResponse::kosong('Kata sandi berhasil diperbarui. Silakan masuk kembali.')
            : ApiResponse::gagal('Kode pemulihan salah atau sudah kedaluwarsa.', 422);
    }
}
