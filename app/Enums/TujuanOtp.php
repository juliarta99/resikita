<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

/**
 * Keperluan sebuah kode OTP.
 *
 * Satu tabel `otp_token` melayani ketiganya. Skema lama punya dua
 * mekanisme berdampingan (`otp_tokens` dan `wa_verifications`);
 * yang kedua dibuang, lihat CLAUDE.md 4.1.
 */
enum TujuanOtp: string implements HasLabel
{
    use ProvidesOptions;

    case VerifikasiEmail = 'verifikasi_email';
    case VerifikasiPhone = 'verifikasi_phone';
    case ResetPassword = 'reset_password';

    public function label(): string
    {
        return match ($this) {
            self::VerifikasiEmail => 'Verifikasi email',
            self::VerifikasiPhone => 'Verifikasi nomor WhatsApp',
            self::ResetPassword => 'Atur ulang kata sandi',
        };
    }

    /** Masa berlaku kode, dalam menit. */
    public function masaBerlakuMenit(): int
    {
        return match ($this) {
            self::VerifikasiEmail, self::VerifikasiPhone => 10,
            self::ResetPassword => 15,
        };
    }

    /**
     * Subjek email, dibedakan per keperluan.
     *
     * Sebelumnya ketiganya memakai satu subjek yang sama, "Kode
     * verifikasi Resikita". Penerima yang baru saja meminta atur ulang
     * kata sandi karena itu melihat subjek yang tidak nyambung dengan
     * yang dimintanya — dan subjek yang tidak nyambung adalah yang paling
     * mudah dikira surel palsu lalu diabaikan.
     */
    public function subjekEmail(): string
    {
        return match ($this) {
            self::VerifikasiEmail => 'Kode verifikasi email Anda',
            self::VerifikasiPhone => 'Kode verifikasi nomor WhatsApp Anda',
            self::ResetPassword => 'Kode untuk mengatur ulang kata sandi',
        };
    }

    /** Kalimat pembuka yang menjelaskan kenapa kode ini dikirim. */
    public function pembukaPesan(): string
    {
        return match ($this) {
            self::VerifikasiEmail => 'Gunakan kode berikut untuk memverifikasi alamat email Anda.',
            self::VerifikasiPhone => 'Gunakan kode berikut untuk memverifikasi nomor WhatsApp Anda.',
            self::ResetPassword => 'Gunakan kode berikut untuk mengatur ulang kata sandi Anda.',
        };
    }

    /** Kanal pengiriman kode. */
    public function channel(): ChannelNotifikasi
    {
        return $this === self::VerifikasiPhone
            ? ChannelNotifikasi::Wa
            : ChannelNotifikasi::Inapp;
    }
}
