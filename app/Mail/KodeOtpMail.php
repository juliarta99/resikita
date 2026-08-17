<?php

declare(strict_types=1);

namespace App\Mail;

use App\Enums\TujuanOtp;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Email berisi kode OTP.
 *
 * Subjeknya mengikuti keperluan kodenya, bukan satu kalimat seragam
 * untuk ketiganya. Penerima yang meminta atur ulang kata sandi lalu
 * menerima surel bersubjek "Kode verifikasi" akan mengira surel itu
 * salah kirim atau palsu — dan surel keamanan yang diabaikan sama tidak
 * bergunanya dengan surel yang tidak pernah terkirim.
 *
 * Kodenya sendiri tidak pernah disimpan di mana pun dalam bentuk polos.
 * Ia hanya hidup di dalam objek ini dan di badan surel yang dikirimkan.
 */
class KodeOtpMail extends Mailable
{
    public function __construct(
        public readonly User $penerima,
        public readonly string $kode,
        public readonly TujuanOtp $tujuan,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->tujuan->subjekEmail().' · '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.kode-otp',
            with: [
                'namaPengguna' => $this->namaSapaan(),
                'kode' => $this->kode,
                'tujuan' => $this->tujuan,
            ],
        );
    }

    /**
     * Sapaan memakai nama depan saja.
     *
     * Nama lengkap Indonesia sering panjang dan bergelar; menyapa dengan
     * seluruhnya membuat kalimat pembuka terbaca seperti surat dinas,
     * bukan pemberitahuan singkat.
     */
    private function namaSapaan(): string
    {
        $bagian = preg_split('/\s+/', trim($this->penerima->name)) ?: [];

        return $bagian[0] ?? $this->penerima->name;
    }
}
