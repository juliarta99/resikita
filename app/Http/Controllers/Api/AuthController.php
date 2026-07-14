<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Domain\OtpService;
use App\Services\Domain\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function __construct(private OtpService $otp, private WalletService $wallet)
    {
    }

    /** Normalisasi nomor ke format 62xxxx untuk WhatsApp. */
    private function normalisasiTelepon(string $phone): string
    {
        $p = preg_replace('/\D+/', '', $phone);
        if (str_starts_with($p, '0')) {
            $p = '62' . substr($p, 1);
        } elseif (! str_starts_with($p, '62')) {
            $p = '62' . $p;
        }

        return $p;
    }

    private function kodeQrUnik(): string
    {
        do {
            $kode = 'NR' . str_pad((string) random_int(1, 99999999), 8, '0', STR_PAD_LEFT);
        } while (User::where('kode_qr', $kode)->exists());

        return $kode;
    }

    private function userPayload(User $user): array
    {
        return [
            'id'             => $user->id,
            'name'           => $user->name,
            'email'          => $user->email,
            'nik'            => $user->nik,
            'tanggal_lahir'  => $user->tanggal_lahir?->format('Y-m-d'),
            'jenis_kelamin'  => $user->jenis_kelamin,
            'phone'          => $user->phone,
            'kode_qr'        => $user->kode_qr,
            'phone_verified' => (bool) $user->phone_verified_at,
            'is_active'      => (bool) $user->is_active,
            'roles'          => $user->getRoleNames(),
            'saldo'          => (float) $this->wallet->saldo($user),
            'bergabung'      => $user->created_at?->toIso8601String(),
        ];
    }

    private function devKode(string $kode): array
    {
        // Saat driver 'log' (dev), kembalikan kode agar mudah diuji tanpa gateway.
        return config('services.whatsapp.driver', 'log') === 'log' ? ['dev_kode' => $kode] : [];
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'nik'           => 'required|digits:16|unique:users,nik',
            'tanggal_lahir' => 'required|date|before:today',
            'jenis_kelamin' => 'required|in:L,P',
            'phone'         => 'required|string|max:20',
            'password'      => 'required|string|min:8',
        ]);

        $phone = $this->normalisasiTelepon($data['phone']);
        if (User::where('phone', $phone)->exists()) {
            return response()->json(['message' => 'Nomor WhatsApp sudah terdaftar.'], 422);
        }

        $user = User::create([
            'name'          => $data['name'],
            'nik'           => $data['nik'],
            'tanggal_lahir' => $data['tanggal_lahir'],
            'jenis_kelamin' => $data['jenis_kelamin'],
            'phone'         => $phone,
            'password'      => Hash::make($data['password']),
            'kode_qr'       => $this->kodeQrUnik(),
            'is_active'     => false,
        ]);
        $user->assignRole('masyarakat');

        $kode = $this->otp->kirim($user, 'register');

        return response()->json([
            'message' => 'Registrasi berhasil. Kode OTP telah dikirim via WhatsApp.',
            'data'    => array_merge(['user_id' => $user->id, 'phone' => $user->phone], $this->devKode($kode)),
        ], 201);
    }

    public function verifyRegister(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|string',
            'kode'  => 'required|digits:6',
        ]);

        $phone = $this->normalisasiTelepon($data['phone']);
        $user = User::where('phone', $phone)->first();

        if (! $user) {
            return response()->json(['message' => 'Pengguna tidak ditemukan.'], 404);
        }
        if (! $this->otp->verifikasi($user, 'register', $data['kode'])) {
            return response()->json(['message' => 'Kode OTP salah atau kedaluwarsa.'], 422);
        }

        $user->update(['phone_verified_at' => now(), 'is_active' => true]);
        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'message' => 'Verifikasi berhasil.',
            'data'    => ['token' => $token, 'user' => $this->userPayload($user)],
        ]);
    }

    public function resendRegisterOtp(Request $request)
    {
        $data = $request->validate(['phone' => 'required|string']);
        $phone = $this->normalisasiTelepon($data['phone']);
        $user = User::where('phone', $phone)->whereNull('phone_verified_at')->first();

        if (! $user) {
            return response()->json(['message' => 'Nomor tidak ditemukan atau sudah terverifikasi.'], 404);
        }

        $kode = $this->otp->kirim($user, 'register');

        return response()->json([
            'message' => 'Kode OTP dikirim ulang.',
            'data'    => $this->devKode($kode),
        ]);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'identifier' => 'required|string', // NIK atau email
            'password'   => 'required|string',
        ]);

        $user = User::where('nik', $data['identifier'])
            ->orWhere('email', $data['identifier'])
            ->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'NIK/email atau kata sandi salah.'], 422);
        }
        if (! $user->phone_verified_at) {
            return response()->json([
                'message' => 'Akun belum terverifikasi. Silakan verifikasi OTP WhatsApp.',
                'code'    => 'not_verified',
                'data'    => ['phone' => $user->phone],
            ], 403);
        }
        if (! $user->is_active) {
            return response()->json(['message' => 'Akun dinonaktifkan.', 'code' => 'inactive'], 403);
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil.',
            'data'    => ['token' => $token, 'user' => $this->userPayload($user)],
        ]);
    }

    public function me(Request $request)
    {
        return response()->json(['data' => $this->userPayload($request->user())]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout berhasil.']);
    }
}
