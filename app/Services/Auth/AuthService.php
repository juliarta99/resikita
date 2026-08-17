<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Enums\Role;
use App\Enums\TujuanOtp;
use App\Exceptions\AturanBisnisException;
use App\Models\Dompet;
use App\Models\User;
use App\Services\Wilayah\WilayahResolverService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Pendaftaran, masuk, dan pemulihan akun.
 *
 * Dipakai komponen Livewire maupun controller API. Keduanya memanggil
 * method yang sama; yang berbeda hanya apa yang dilakukan setelahnya,
 * web membuat sesi, mobile menerbitkan token Sanctum.
 *
 * Identitas utama adalah email. Nomor telepon opsional dan hanya dipakai
 * untuk notifikasi WhatsApp. NIK tidak ada dan tidak boleh
 * ditambahkan, lihat CLAUDE.md 4.2.
 */
class AuthService
{
    /** Batas percobaan masuk per email per menit. */
    private const BATAS_LOGIN = 5;

    public function __construct(
        private readonly OtpService $otp,
        private readonly WilayahResolverService $resolver,
    ) {}

    /**
     * Pendaftaran mandiri warga.
     *
     * Akun langsung aktif dan bisa dipakai; verifikasi email dikirim
     * tapi tidak menghalangi. Warga yang ingin melaporkan tumpukan
     * sampah di depan rumahnya sebaiknya bisa langsung melakukannya,
     * menahan mereka di layar verifikasi adalah cara cepat kehilangan
     * laporan yang justru ingin kita kumpulkan.
     *
     * @param  array<string, mixed>  $data
     */
    public function daftar(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $wilayahId = $data['wilayah_id'] ?? null;

            // Kalau warga mengirim koordinat tapi tidak memilih wilayah,
            // domisilinya diselesaikan otomatis supaya konteks lokal
            // chatbot dan direktori terdekat langsung berguna.
            if ($wilayahId === null && isset($data['latitude'], $data['longitude'])) {
                $wilayahId = $this->resolver
                    ->resolve((float) $data['latitude'], (float) $data['longitude'])
                    ->desaId;
            }

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'phone' => $data['phone'] ?? null,
                'wilayah_id' => $wilayahId,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'kode_qr' => (string) Str::ulid(),
                'is_active' => true,
            ]);

            $user->assignRole(Role::Masyarakat->value);

            // Dompet dibuat bersamaan agar tidak ada warga yang menyetor
            // sampah lalu menemukan dirinya tanpa tempat menampung saldo.
            // Mutasi saldonya sendiri hanya boleh lewat DompetService.
            Dompet::create(['user_id' => $user->id, 'saldo' => 0]);

            $this->otp->terbitkan($user, TujuanOtp::VerifikasiEmail);

            return $user;
        });
    }

    /**
     * Periksa kredensial dan kembalikan penggunanya.
     *
     * Tidak membuat sesi dan tidak menerbitkan token, itu urusan
     * pemanggil, karena berbeda antara web dan mobile.
     */
    public function autentikasi(string $email, string $password): User
    {
        $kunciLaju = 'login:'.Str::lower($email);

        if (RateLimiter::tooManyAttempts($kunciLaju, self::BATAS_LOGIN)) {
            throw AturanBisnisException::karena(
                'Terlalu banyak percobaan masuk. Coba lagi dalam '.RateLimiter::availableIn($kunciLaju).' detik.',
                429,
            );
        }

        $user = User::where('email', $email)->first();

        // Pesan yang sama untuk email tidak terdaftar maupun kata sandi
        // salah. Membedakan keduanya memberi tahu penyerang email mana
        // yang terdaftar di sistem.
        if ($user === null || ! Hash::check($password, $user->password)) {
            RateLimiter::hit($kunciLaju, 60);

            throw AturanBisnisException::karena('Email atau kata sandi salah.', 401);
        }

        if (! $user->is_active) {
            throw AturanBisnisException::karena(
                'Akun Anda sedang dinonaktifkan. Hubungi admin Resikita.',
                403,
            );
        }

        RateLimiter::clear($kunciLaju);

        return $user;
    }

    /**
     * Terbitkan token Sanctum untuk aplikasi mobile.
     *
     * Token diberi kemampuan sesuai permission role, sehingga token yang
     * dicuri tidak bisa dipakai melampaui apa yang boleh dilakukan
     * pemiliknya.
     */
    public function terbitkanToken(User $user, string $namaPerangkat = 'mobile'): string
    {
        $kemampuan = $user->getAllPermissions()->pluck('name')->all();

        return $user->createToken($namaPerangkat, $kemampuan === [] ? ['*'] : $kemampuan)
            ->plainTextToken;
    }

    /** Cabut token yang sedang dipakai. */
    public function cabutTokenSaatIni(User $user): void
    {
        $token = $user->currentAccessToken();

        if ($token !== null && method_exists($token, 'delete')) {
            $token->delete();
        }
    }

    /** Cabut seluruh token pengguna, mis. saat kata sandi diganti. */
    public function cabutSemuaToken(User $user): void
    {
        $user->tokens()->delete();
    }

    /** Kirim kode verifikasi email. */
    public function kirimVerifikasiEmail(User $user): ?string
    {
        if ($user->email_verified_at !== null) {
            throw AturanBisnisException::karena('Email ini sudah terverifikasi.');
        }

        return $this->otp->terbitkan($user, TujuanOtp::VerifikasiEmail);
    }

    public function verifikasiEmail(User $user, string $kode): bool
    {
        if (! $this->otp->verifikasiDanKonsumsi($user, TujuanOtp::VerifikasiEmail, $kode)) {
            return false;
        }

        $user->update(['email_verified_at' => now()]);

        return true;
    }

    public function kirimVerifikasiPhone(User $user): ?string
    {
        return $this->otp->terbitkan($user, TujuanOtp::VerifikasiPhone);
    }

    public function verifikasiPhone(User $user, string $kode): bool
    {
        if (! $this->otp->verifikasiDanKonsumsi($user, TujuanOtp::VerifikasiPhone, $kode)) {
            return false;
        }

        $user->update(['phone_verified_at' => now()]);

        return true;
    }

    /**
     * Mulai pemulihan kata sandi.
     *
     * Selalu melapor berhasil, bahkan untuk email yang tidak terdaftar.
     * Kalau tidak, formulir lupa kata sandi berubah menjadi alat untuk
     * memeriksa email siapa saja yang punya akun di Resikita.
     */
    public function mintaResetPassword(string $email): void
    {
        $user = User::where('email', $email)->first();

        if ($user !== null && $user->is_active) {
            $this->otp->terbitkan($user, TujuanOtp::ResetPassword);
        }
    }

    /**
     * Setel kata sandi baru setelah kode terverifikasi.
     *
     * Seluruh token Sanctum dicabut: kalau akun ini dipulihkan karena
     * diduga diambil alih orang lain, sesi mobile penyerang harus ikut
     * mati bersamaan.
     */
    public function resetPassword(string $email, string $kode, string $passwordBaru): bool
    {
        $user = User::where('email', $email)->first();

        if ($user === null) {
            return false;
        }

        if (! $this->otp->verifikasiDanKonsumsi($user, TujuanOtp::ResetPassword, $kode)) {
            return false;
        }

        DB::transaction(function () use ($user, $passwordBaru): void {
            $user->update(['password' => Hash::make($passwordBaru)]);
            $this->cabutSemuaToken($user);
        });

        return true;
    }

    /** Ganti kata sandi oleh pengguna yang sedang masuk. */
    public function gantiPassword(User $user, string $passwordLama, string $passwordBaru): void
    {
        if (! Hash::check($passwordLama, $user->password)) {
            throw AturanBisnisException::karena('Kata sandi lama tidak cocok.');
        }

        if (Hash::check($passwordBaru, $user->password)) {
            throw AturanBisnisException::karena('Kata sandi baru harus berbeda dari yang lama.');
        }

        /*
         * Token dicabut sama seperti pada alur reset lewat kode.
         * Sebagian besar orang mengganti kata sandi justru karena
         * menduga ada yang tahu kata sandi lamanya; membiarkan sesi
         * mobile lama tetap hidup membuat penggantian itu tidak
         * menyelesaikan apa pun.
         */
        DB::transaction(function () use ($user, $passwordBaru): void {
            $user->update(['password' => Hash::make($passwordBaru)]);
            $this->cabutSemuaToken($user);
        });
    }
}
