<?php

namespace App\Services\Domain;

use App\Models\OtpToken;
use App\Models\User;
use App\Services\Integration\WhatsappOtpService;
use Illuminate\Support\Facades\Hash;

class OtpService
{
    public function __construct(private WhatsappOtpService $wa)
    {
    }

    /** Buat & kirim OTP. Mengembalikan kode polos (untuk kebutuhan dev/log). */
    public function kirim(User $user, string $tujuan, int $berlakuMenit = 5): string
    {
        $kode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Batalkan OTP tujuan sama yang belum terverifikasi
        OtpToken::where('user_id', $user->id)->where('tujuan', $tujuan)
            ->whereNull('verified_at')->delete();

        OtpToken::create([
            'user_id'    => $user->id,
            'tujuan'     => $tujuan,
            'kode_hash'  => Hash::make($kode),
            'expires_at' => now()->addMinutes($berlakuMenit),
        ]);

        $this->wa->send(
            $user->phone,
            "Kode verifikasi Niti Resik Anda: {$kode}. Berlaku {$berlakuMenit} menit. Jangan bagikan kepada siapa pun."
        );

        return $kode;
    }

    /** Verifikasi kode untuk tujuan tertentu. */
    public function verifikasi(User $user, string $tujuan, string $kode): bool
    {
        $token = OtpToken::where('user_id', $user->id)->where('tujuan', $tujuan)
            ->whereNull('verified_at')->where('expires_at', '>', now())
            ->latest()->first();

        if (! $token || ! Hash::check($kode, $token->kode_hash)) {
            return false;
        }

        $token->update(['verified_at' => now()]);

        return true;
    }

    /**
     * Konsumsi verifikasi untuk aksi sensitif (step-up).
     * True jika ada verifikasi valid & belum terpakai dalam jendela waktu.
     */
    public function konsumsi(User $user, string $tujuan, int $jendelaMenit = 10): bool
    {
        $token = OtpToken::where('user_id', $user->id)->where('tujuan', $tujuan)
            ->whereNotNull('verified_at')->whereNull('used_at')
            ->where('verified_at', '>=', now()->subMinutes($jendelaMenit))
            ->latest()->first();

        if (! $token) {
            return false;
        }

        $token->update(['used_at' => now()]);

        return true;
    }
}