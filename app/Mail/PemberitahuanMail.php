<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Str;

/**
 * Pemberitahuan umum berkop Resikita.
 *
 * Satu Mailable untuk seluruh kabar yang bentuknya sama — judul, isi,
 * dan satu ajakan opsional. Hasil verifikasi UMKM memakainya sekarang,
 * dan pemberitahuan berikutnya tinggal ikut tanpa menambah templat baru.
 *
 * Pesan masuk sebagai satu string dan dipecah menjadi paragraf di sini,
 * bukan di pemanggilnya. Sumber pesan yang sama juga dikirim ke WhatsApp
 * dan disimpan ke tabel notifikasi, jadi ia harus tetap berupa teks
 * biasa — bukan HTML yang hanya cocok untuk satu kanal.
 */
class PemberitahuanMail extends Mailable
{
    public function __construct(
        public readonly User $penerima,
        public readonly string $judul,
        public readonly string $pesan,
        public readonly ?string $actionUrl = null,
        public readonly string $actionLabel = 'Buka Resikita',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->judul.' · '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.pemberitahuan',
            with: [
                'namaPengguna' => $this->namaSapaan(),
                'judul' => $this->judul,
                'paragraf' => $this->paragraf(),
                'actionUrl' => $this->actionUrl,
                'actionLabel' => $this->actionLabel,
                'ringkas' => Str::limit($this->pesan, 120),
            ],
        );
    }

    /** @return array<int, string> */
    private function paragraf(): array
    {
        $bagian = preg_split('/\n\s*\n/', trim($this->pesan)) ?: [];

        return array_values(array_filter(array_map(
            static fn (string $b): string => trim(preg_replace('/\s+/', ' ', $b) ?? ''),
            $bagian,
        )));
    }

    private function namaSapaan(): string
    {
        $bagian = preg_split('/\s+/', trim($this->penerima->name)) ?: [];

        return $bagian[0] ?? $this->penerima->name;
    }
}
