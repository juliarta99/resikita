<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Enums\TujuanOtp;
use App\Exceptions\AturanBisnisException;
use App\Models\Dompet;
use App\Models\User;
use App\Services\Auth\AkunService;
use App\Services\Auth\AuthService;
use App\Services\Auth\OtpService;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    $this->auth = app(AuthService::class);
    $this->otp = app(OtpService::class);

    $this->berkas = [
        'name' => 'Ni Kadek Sari',
        'email' => 'sari@contoh.id',
        'password' => 'rahasia-yang-panjang',
    ];
});

it('mendaftarkan warga lengkap dengan role, dompet, dan kode QR', function (): void {
    $user = $this->auth->daftar($this->berkas);

    expect($user->hasRole(Role::Masyarakat->value))->toBeTrue()
        ->and($user->is_active)->toBeTrue()
        ->and($user->kode_qr)->not->toBeNull()
        ->and(strlen($user->kode_qr))->toBe(26)
        ->and(Dompet::where('user_id', $user->id)->exists())->toBeTrue();
});

it('tidak menyimpan NIK dalam bentuk apa pun', function (): void {
    // Penjaga permanen terhadap CLAUDE.md 4.2. Kalau kolom ini kembali,
    // uji ini yang pertama menemukannya.
    $user = $this->auth->daftar($this->berkas);

    expect(array_keys($user->getAttributes()))
        ->not->toContain('nik')
        ->not->toContain('nip');
});

it('membiarkan warga langsung memakai akun tanpa menunggu verifikasi email', function (): void {
    $user = $this->auth->daftar($this->berkas);

    // Kode verifikasi terkirim, tapi akun sudah bisa dipakai.
    expect($user->email_verified_at)->toBeNull()
        ->and($user->is_active)->toBeTrue()
        ->and($user->otpTokens()->where('tujuan', TujuanOtp::VerifikasiEmail)->exists())->toBeTrue();
});

it('menerima kredensial yang benar', function (): void {
    $this->auth->daftar($this->berkas);

    $user = $this->auth->autentikasi('sari@contoh.id', 'rahasia-yang-panjang');

    expect($user->email)->toBe('sari@contoh.id');
});

it('memakai pesan galat yang sama untuk email tak terdaftar dan kata sandi salah', function (): void {
    $this->auth->daftar($this->berkas);

    $pesan = [];

    try {
        $this->auth->autentikasi('sari@contoh.id', 'kata-sandi-keliru');
    } catch (AturanBisnisException $e) {
        $pesan[] = $e->getMessage();
    }

    try {
        $this->auth->autentikasi('tidak-ada@contoh.id', 'apa-saja');
    } catch (AturanBisnisException $e) {
        $pesan[] = $e->getMessage();
    }

    // Pesan yang berbeda akan memberi tahu penyerang email mana yang
    // terdaftar di Resikita.
    expect($pesan)->toHaveCount(2)
        ->and($pesan[0])->toBe($pesan[1])
        ->and($pesan[0])->toBe('Email atau kata sandi salah.');
});

it('menolak akun yang dinonaktifkan', function (): void {
    $user = $this->auth->daftar($this->berkas);
    $user->update(['is_active' => false]);

    $this->auth->autentikasi('sari@contoh.id', 'rahasia-yang-panjang');
})->throws(AturanBisnisException::class, 'dinonaktifkan');

it('membatasi laju percobaan masuk', function (): void {
    $this->auth->daftar($this->berkas);

    for ($i = 0; $i < 5; $i++) {
        try {
            $this->auth->autentikasi('sari@contoh.id', 'salah');
        } catch (AturanBisnisException) {
            // percobaan gagal yang diharapkan
        }
    }

    // Percobaan keenam ditolak karena laju, bukan karena kredensial.
    expect(fn () => $this->auth->autentikasi('sari@contoh.id', 'rahasia-yang-panjang'))
        ->toThrow(AturanBisnisException::class, 'Terlalu banyak percobaan masuk');

    RateLimiter::clear('login:sari@contoh.id');
});

it('tidak membocorkan email terdaftar lewat permintaan reset kata sandi', function (): void {
    // Tidak melempar galat untuk email yang tidak ada. Kalau melempar,
    // formulir lupa kata sandi jadi alat pemeriksa keanggotaan.
    $this->auth->mintaResetPassword('sama-sekali-tidak-ada@contoh.id');
})->throwsNoExceptions();

it('mengatur ulang kata sandi lewat kode dan mencabut seluruh token', function (): void {
    $user = $this->auth->daftar($this->berkas);
    $this->auth->terbitkanToken($user, 'ponsel');

    expect($user->tokens()->count())->toBe(1);

    $kode = $this->otp->terbitkan($user, TujuanOtp::ResetPassword);
    $berhasil = $this->auth->resetPassword('sari@contoh.id', $kode, 'kata-sandi-baru-sekali');

    expect($berhasil)->toBeTrue()
        ->and(Hash::check('kata-sandi-baru-sekali', $user->fresh()->password))->toBeTrue()
        ->and($user->fresh()->tokens()->count())->toBe(0);
});

it('menolak kode OTP yang salah', function (): void {
    $user = $this->auth->daftar($this->berkas);
    $this->otp->terbitkan($user, TujuanOtp::ResetPassword);

    expect($this->otp->verifikasi($user, TujuanOtp::ResetPassword, '000000'))->toBeFalse();
});

it('menolak kode OTP yang sudah kedaluwarsa', function (): void {
    $user = $this->auth->daftar($this->berkas);
    $kode = $this->otp->terbitkan($user, TujuanOtp::ResetPassword);

    // Pendaftaran sudah menerbitkan satu token verifikasi email, jadi
    // token yang dituju harus dipilih lewat tujuannya, bukan sekadar
    // yang terbaru.
    $user->otpTokens()
        ->where('tujuan', TujuanOtp::ResetPassword)
        ->latest('id')
        ->first()
        ->update(['expires_at' => now()->subMinute()]);

    expect($this->otp->verifikasi($user, TujuanOtp::ResetPassword, $kode))->toBeFalse();
});

it('menghanguskan kode lama saat kode baru diterbitkan', function (): void {
    $user = $this->auth->daftar($this->berkas);

    $kodeLama = $this->otp->terbitkan($user, TujuanOtp::ResetPassword);
    $kodeBaru = $this->otp->terbitkan($user, TujuanOtp::ResetPassword);

    expect($this->otp->verifikasi($user, TujuanOtp::ResetPassword, $kodeLama))->toBeFalse()
        ->and($this->otp->verifikasi($user, TujuanOtp::ResetPassword, $kodeBaru))->toBeTrue();
});

it('mencegah satu kode dipakai dua kali', function (): void {
    $user = $this->auth->daftar($this->berkas);
    $kode = $this->otp->terbitkan($user, TujuanOtp::ResetPassword);

    expect($this->otp->verifikasiDanKonsumsi($user, TujuanOtp::ResetPassword, $kode))->toBeTrue()
        ->and($this->otp->verifikasiDanKonsumsi($user, TujuanOtp::ResetPassword, $kode))->toBeFalse();
});

it('menyimpan kode OTP sebagai hash, bukan teks terbuka', function (): void {
    $user = $this->auth->daftar($this->berkas);
    $kode = $this->otp->terbitkan($user, TujuanOtp::ResetPassword);

    $token = $user->otpTokens()
        ->where('tujuan', TujuanOtp::ResetPassword)
        ->latest('id')
        ->first();

    expect($token->kode_hash)->not->toBe($kode)
        ->and(Hash::check($kode, $token->kode_hash))->toBeTrue();
});

it('memberi token mobile kemampuan sesuai permission rolenya', function (): void {
    $user = $this->auth->daftar($this->berkas);

    $this->auth->terbitkanToken($user, 'ponsel-sari');

    $kemampuan = $user->tokens()->first()->abilities;

    expect($kemampuan)->toContain('laporan.buat')
        ->and($kemampuan)->toContain('klasifikasi.buat')
        // Warga tidak boleh memverifikasi laporan, jadi tokennya pun tidak.
        ->and($kemampuan)->not->toContain('laporan.verifikasi')
        ->and($kemampuan)->not->toContain('pengguna.kelola');
});

it('menolak penggantian kata sandi dengan kata sandi lama yang keliru', function (): void {
    $user = $this->auth->daftar($this->berkas);

    $this->auth->gantiPassword($user, 'bukan-yang-ini', 'kata-sandi-baru-sekali');
})->throws(AturanBisnisException::class, 'Kata sandi lama tidak cocok');

it('menolak kata sandi baru yang sama dengan yang lama', function (): void {
    $user = $this->auth->daftar($this->berkas);

    $this->auth->gantiPassword($user, 'rahasia-yang-panjang', 'rahasia-yang-panjang');
})->throws(AturanBisnisException::class, 'harus berbeda');

it('membatalkan status verifikasi ketika email diganti', function (): void {
    $user = $this->auth->daftar($this->berkas);
    $user->update(['email_verified_at' => now()]);

    app(AkunService::class)->perbarui($user, ['email' => 'alamat-baru@contoh.id']);

    expect($user->fresh()->email_verified_at)->toBeNull();
});

it('mencabut token mobile saat akun dinonaktifkan', function (): void {
    $user = User::factory()->withRole(Role::Petugas)->create();
    $this->auth->terbitkanToken($user, 'ponsel');

    expect($user->tokens()->count())->toBe(1);

    app(AkunService::class)->nonaktifkan($user);

    // Tanpa ini, akun yang dinonaktifkan di web masih bisa dipakai dari
    // ponsel sampai tokennya kedaluwarsa sendiri.
    expect($user->fresh()->tokens()->count())->toBe(0)
        ->and($user->fresh()->is_active)->toBeFalse();
});
