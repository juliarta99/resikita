<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ChannelNotifikasi;
use App\Enums\TujuanOtp;
use App\Mail\KodeOtpMail;
use App\Models\User;
use App\Services\Integration\FonnteService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

/**
 * Kirim kode OTP ke pengguna.
 *
 * Berjalan lewat antrean karena memanggil penyedia luar (CLAUDE.md 3
 * butir 8). Kalau Fonnte sedang lambat, pengguna tidak boleh ikut
 * menunggu di layar pendaftaran.
 *
 * Kode polos hanya hidup di dalam Job ini dan tidak pernah disimpan,
 * yang tersimpan di basis data adalah hash-nya.
 */
class KirimKodeOtpJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(
        public readonly int $userId,
        public readonly string $kode,
        public readonly TujuanOtp $tujuan,
    ) {}

    public function handle(FonnteService $fonnte): void
    {
        $user = User::find($this->userId);

        if ($user === null) {
            return;
        }

        if ($this->tujuan->channel() === ChannelNotifikasi::Wa && $user->phone !== null) {
            $fonnte->kirim($user->phone, $this->susunPesanWa());

            return;
        }

        Mail::to($user->email)->send(new KodeOtpMail($user, $this->kode, $this->tujuan));
    }

    /**
     * Versi teks polos untuk WhatsApp.
     *
     * Kanal ini tidak mengenal HTML, jadi ia memang harus punya
     * susunannya sendiri — bukan templat surel yang dilucuti tagnya.
     */
    private function susunPesanWa(): string
    {
        $menit = $this->tujuan->masaBerlakuMenit();
        $nama = config('app.name');

        return "{$this->tujuan->subjekEmail()} {$nama}: {$this->kode}\n"
            ."Berlaku $menit menit. Jangan bagikan kode ini kepada siapa pun, termasuk kepada orang yang mengaku petugas $nama.";
    }
}
