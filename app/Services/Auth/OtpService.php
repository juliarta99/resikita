<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Enums\TujuanOtp;
use App\Exceptions\AturanBisnisException;
use App\Jobs\KirimKodeOtpJob;
use App\Models\OtpToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Kode sekali pakai untuk verifikasi dan pemulihan akun.
 *
 * Skema Niti Resik punya dua mekanisme berdampingan, `otp_tokens` dan
 * `wa_verifications`, dengan alur yang mirip tapi tidak identik, jadi
 * memperbaiki bug di salah satunya tidak memperbaiki yang lain. Sekarang
 * satu tabel dan satu Service melayani ketiga keperluan.
 *
 * Tiga sikap keamanan yang sengaja diambil:
 *
 * - Kode disimpan sebagai hash. OTP adalah kredensial sementara, jadi
 *   diperlakukan seperti kata sandi. Basis data yang bocor tidak boleh
 *   berisi kode yang masih bisa dipakai.
 *
 * - Permintaan kode dibatasi lajunya. Tanpa itu, endpoint OTP menjadi
 *   alat kirim pesan gratis atas beban nomor orang lain.
 *
 * - Verifikasi selalu menyatakan gagal dengan pesan yang sama, entah
 *   kodenya salah, kedaluwarsa, atau memang tidak pernah ada. Pesan
 *   yang membedakan ketiganya memberi tahu penyerang mana tebakan yang
 *   mendekati.
 */
class OtpService
{
    /** Batas permintaan kode per pengguna per jam. */
    private const BATAS_PERMINTAAN = 5;

    /** Batas percobaan verifikasi per kode. */
    private const BATAS_PERCOBAAN = 5;

    /**
     * Terbitkan kode baru dan kirimkan lewat kanal yang sesuai tujuannya.
     *
     * Mengembalikan kode polos hanya ketika aplikasi tidak berjalan di
     * produksi, supaya alur pendaftaran bisa diuji tanpa gateway
     * sungguhan. Di produksi nilainya selalu null.
     */
    public function terbitkan(User $user, TujuanOtp $tujuan): ?string
    {
        $kunciLaju = "otp:$tujuan->value:$user->id";

        if (RateLimiter::tooManyAttempts($kunciLaju, self::BATAS_PERMINTAAN)) {
            $detik = RateLimiter::availableIn($kunciLaju);

            throw AturanBisnisException::karena(
                'Terlalu banyak permintaan kode. Coba lagi dalam '.ceil($detik / 60).' menit.',
                429,
            );
        }

        if ($tujuan->channel()->butuhNomorTelepon() && $user->phone === null) {
            throw AturanBisnisException::karena(
                'Nomor WhatsApp belum terdaftar pada akun ini.',
            );
        }

        RateLimiter::hit($kunciLaju, 3600);

        $kode = str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($user, $tujuan, $kode): void {
            // Kode lama untuk tujuan yang sama dianggap hangus. Membiarkan
            // beberapa kode aktif sekaligus memperbesar peluang tebakan
            // tanpa memberi manfaat apa pun kepada pengguna.
            OtpToken::query()
                ->where('user_id', $user->id)
                ->where('tujuan', $tujuan)
                ->whereNull('used_at')
                ->update(['used_at' => now()]);

            OtpToken::create([
                'user_id' => $user->id,
                'tujuan' => $tujuan,
                'kode_hash' => Hash::make($kode),
                'expires_at' => now()->addMinutes($tujuan->masaBerlakuMenit()),
            ]);
        });

        KirimKodeOtpJob::dispatch($user->id, $kode, $tujuan);

        return app()->isProduction() ? null : $kode;
    }

    /**
     * Periksa kode. Token yang cocok ditandai terverifikasi, tapi belum
     * dianggap terpakai, pemakaiannya dicatat oleh konsumsi().
     */
    public function verifikasi(User $user, TujuanOtp $tujuan, string $kode): bool
    {
        $kunciLaju = "otp-cek:$tujuan->value:$user->id";

        if (RateLimiter::tooManyAttempts($kunciLaju, self::BATAS_PERCOBAAN)) {
            throw AturanBisnisException::karena(
                'Terlalu banyak percobaan. Minta kode baru dan coba lagi nanti.',
                429,
            );
        }

        $token = OtpToken::query()
            ->where('user_id', $user->id)
            ->untuk($tujuan)
            ->berlaku()
            ->latest('id')
            ->first();

        if ($token === null || ! Hash::check($kode, $token->kode_hash)) {
            RateLimiter::hit($kunciLaju, 900);

            return false;
        }

        RateLimiter::clear($kunciLaju);

        $token->update(['verified_at' => now()]);

        return true;
    }

    /**
     * Pakai verifikasi yang baru saja berhasil, untuk tindakan sensitif.
     *
     * Dipisahkan dari verifikasi() supaya alur dua langkah bisa
     * dilakukan: pengguna memasukkan kode di satu layar, lalu mengisi
     * kata sandi baru di layar berikutnya. Jendela waktunya pendek agar
     * verifikasi yang menganggur tidak bisa dipakai jauh kemudian.
     */
    public function konsumsi(User $user, TujuanOtp $tujuan, int $jendelaMenit = 10): bool
    {
        $token = OtpToken::query()
            ->where('user_id', $user->id)
            ->untuk($tujuan)
            ->whereNotNull('verified_at')
            ->whereNull('used_at')
            ->where('verified_at', '>=', now()->subMinutes($jendelaMenit))
            ->latest('id')
            ->first();

        if ($token === null) {
            return false;
        }

        $token->update(['used_at' => now()]);

        return true;
    }

    /** Verifikasi sekaligus konsumsi, untuk alur satu langkah. */
    public function verifikasiDanKonsumsi(User $user, TujuanOtp $tujuan, string $kode): bool
    {
        return $this->verifikasi($user, $tujuan, $kode)
            && $this->konsumsi($user, $tujuan);
    }

    /** Bersihkan token kedaluwarsa. Dipanggil dari penjadwal. */
    public function bersihkanKedaluwarsa(): int
    {
        return OtpToken::query()
            ->where('expires_at', '<', now()->subDay())
            ->delete();
    }
}
