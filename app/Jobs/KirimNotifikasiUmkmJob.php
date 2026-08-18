<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\PemberitahuanMail;
use App\Models\User;
use App\Services\Integration\FonnteService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

/**
 * Sampaikan hasil verifikasi toko ke luar aplikasi.
 *
 * Berjalan lewat antrean karena memanggil penyedia luar (CLAUDE.md 3
 * butir 8). Admin yang menekan "setujui" atau "tolak" tidak boleh ikut
 * menunggu Fonnte atau peladen surel menjawab.
 *
 * Baris in-app-nya sudah ditulis AkunService di dalam transaction, jadi
 * kegagalan job ini tidak menghilangkan pemberitahuan dari aplikasi,
 * yang hilang hanya salinannya di WhatsApp atau email.
 *
 * WhatsApp didahulukan bila nomornya ada. Pemilik UMKM kecil jauh lebih
 * sering membuka WhatsApp daripada email, dan hasil verifikasi ini
 * termasuk kabar yang mereka tunggu.
 */
class KirimNotifikasiUmkmJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public readonly int $userId,
        public readonly string $judul,
        public readonly string $pesan,
        public readonly ?string $actionUrl = null,
    ) {}

    public function handle(FonnteService $fonnte): void
    {
        $user = User::find($this->userId);

        if ($user === null) {
            return;
        }

        if ($user->phone !== null) {
            $fonnte->kirim($user->phone, $this->susunPesanWa());

            return;
        }

        Mail::to($user->email)->send(
            new PemberitahuanMail($user, $this->judul, $this->pesan, $this->actionUrl),
        );
    }

    /** Versi teks polos; WhatsApp tidak mengenal HTML. */
    private function susunPesanWa(): string
    {
        $pesan = "{$this->judul}\n\n{$this->pesan}";

        return $this->actionUrl === null
            ? $pesan
            : $pesan."\n\nBuka: {$this->actionUrl}";
    }
}
