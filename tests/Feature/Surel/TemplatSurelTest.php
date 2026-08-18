<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Enums\TujuanOtp;
use App\Jobs\KirimKodeOtpJob;
use App\Jobs\KirimNotifikasiUmkmJob;
use App\Mail\KodeOtpMail;
use App\Mail\PemberitahuanMail;
use App\Models\User;
use App\Services\Integration\FonnteService;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

/**
 * Surel keluar Resikita.
 *
 * Dua hal yang diuji di sini pernah salah sekaligus tidak menimbulkan
 * galat apa pun: subjek yang seragam untuk tiga keperluan berbeda, dan
 * logo yang tidak pernah benar-benar tersemat karena komponen anonim
 * punya cakupan variabelnya sendiri. Keduanya hanya ketahuan dengan
 * memeriksa isi surel yang sungguh-sungguh terbentuk.
 */
beforeEach(function (): void {
    $this->seed(RoleSeeder::class);

    // Fonnte dipalsukan supaya jalur WhatsApp tidak menyentuh jaringan.
    Http::fake(['*' => Http::response(['id' => ['demo-1']])]);

    $this->penerima = User::factory()->withRole(Role::Masyarakat)->create([
        'name' => 'Ni Kadek Sari Dewi',
        'email' => 'kadek@contoh.id',
        'phone' => null,
    ]);
});

describe('subjek', function (): void {
    it('membedakan subjek menurut keperluan kodenya', function (): void {
        $subjek = collect(TujuanOtp::cases())
            ->mapWithKeys(fn (TujuanOtp $t): array => [
                $t->value => (new KodeOtpMail($this->penerima, '482913', $t))->envelope()->subject,
            ]);

        // Dulu ketiganya "Kode verifikasi Resikita". Penerima yang meminta
        // atur ulang kata sandi melihat subjek yang tidak nyambung dengan
        // permintaannya, dan surel keamanan yang tidak dikenali adalah
        // surel yang diabaikan.
        expect($subjek->unique())->toHaveCount(3)
            ->and($subjek['reset_password'])->toContain('mengatur ulang kata sandi')
            ->and($subjek['verifikasi_email'])->toContain('verifikasi email')
            ->and($subjek['verifikasi_phone'])->toContain('nomor WhatsApp');
    });

    it('menyertakan nama aplikasi pada subjek', function (): void {
        $subjek = (new KodeOtpMail($this->penerima, '482913', TujuanOtp::ResetPassword))
            ->envelope()->subject;

        expect($subjek)->toContain(config('app.name'));
    });
});

describe('isi surel kode', function (): void {
    it('memuat kode, sapaan nama depan, dan peringatan keamanan', function (): void {
        $html = (new KodeOtpMail($this->penerima, '482913', TujuanOtp::ResetPassword))->render();

        expect($html)->toContain('482913')
            // Nama depan saja: nama lengkap Indonesia sering panjang dan
            // membuat kalimat pembuka terbaca seperti surat dinas.
            ->and($html)->toContain('Halo, Ni')
            ->and($html)->toContain('Jangan bagikan kode ini')
            ->and($html)->toContain('Berlaku 15 menit');
    });

    it('memakai tata letak tabel, bukan CSS modern yang tidak dikenal klien surel', function (): void {
        $html = (new KodeOtpMail($this->penerima, '482913', TujuanOtp::ResetPassword))->render();

        expect($html)->toContain('role="presentation"')
            ->and($html)->not->toContain('display:flex')
            ->and($html)->not->toContain('display:grid');
    });
});

describe('isi surel pemberitahuan', function (): void {
    it('memecah pesan menjadi paragraf dan menampilkan tombol tindakan', function (): void {
        $html = (new PemberitahuanMail(
            $this->penerima,
            'Toko Anda sudah aktif',
            "Selamat, toko Anda lolos verifikasi.\n\nLengkapi alamat asal pengiriman.",
            'https://resikita.test/umkm',
        ))->render();

        expect($html)->toContain('Toko Anda sudah aktif')
            ->and($html)->toContain('lolos verifikasi')
            ->and($html)->toContain('Lengkapi alamat asal pengiriman')
            ->and($html)->toContain('https://resikita.test/umkm');
    });

    it('menghilangkan tombol ketika tidak ada tautan tindakan', function (): void {
        $html = (new PemberitahuanMail($this->penerima, 'Kabar biasa', 'Isi pesannya.'))->render();

        expect($html)->toContain('Isi pesannya.')
            ->and($html)->not->toContain('Kalau tombol di atas tidak berfungsi');
    });
});

describe('logo', function (): void {
    it('menyematkan logo ke dalam berkas surelnya saat benar-benar dikirim', function (): void {
        config(['mail.default' => 'array']);

        Mail::to($this->penerima->email)
            ->send(new KodeOtpMail($this->penerima, '482913', TujuanOtp::ResetPassword));

        $terkirim = Mail::getSymfonyTransport()->messages();

        expect($terkirim)->toHaveCount(1);

        $mentah = $terkirim[0]->getOriginalMessage()->toString();

        // Logo yang ditautkan ke peladen sendiri diblokir banyak klien
        // surel dan mustahil dibuka kalau APP_URL tidak terjangkau dari
        // internet. Yang disematkan tetap tampil tanpa koneksi keluar.
        expect($mentah)->toContain('Content-Disposition: inline')
            ->and($mentah)->toContain('image/png')
            ->and($mentah)->toContain('cid:');
    });
});

describe('pemilihan kanal', function (): void {
    it('mengirim kode lewat email ketika kanalnya bukan WhatsApp', function (): void {
        Mail::fake();

        (new KirimKodeOtpJob($this->penerima->id, '482913', TujuanOtp::ResetPassword))
            ->handle(app(FonnteService::class));

        Mail::assertSent(
            KodeOtpMail::class,
            fn (KodeOtpMail $mail): bool => $mail->hasTo('kadek@contoh.id') && $mail->kode === '482913',
        );
    });

    it('mengirim kode verifikasi nomor lewat WhatsApp, bukan email', function (): void {
        Mail::fake();

        $this->penerima->update(['phone' => '081234567890']);

        (new KirimKodeOtpJob($this->penerima->id, '482913', TujuanOtp::VerifikasiPhone))
            ->handle(app(FonnteService::class));

        Mail::assertNothingSent();
    });

    it('mengirim pemberitahuan UMKM lewat email ketika nomor tidak ada', function (): void {
        Mail::fake();

        (new KirimNotifikasiUmkmJob(
            $this->penerima->id,
            'Pendaftaran toko perlu diperbaiki',
            'Alamat usaha belum lengkap.',
            'https://resikita.test/umkm/status',
        ))->handle(app(FonnteService::class));

        Mail::assertSent(
            PemberitahuanMail::class,
            fn (PemberitahuanMail $mail): bool => $mail->hasTo('kadek@contoh.id')
                && $mail->actionUrl === 'https://resikita.test/umkm/status',
        );
    });

    it('mendahulukan WhatsApp untuk pemberitahuan UMKM bila nomornya ada', function (): void {
        Mail::fake();

        $this->penerima->update(['phone' => '081234567890']);

        (new KirimNotifikasiUmkmJob($this->penerima->id, 'Judul', 'Isi pesan.'))
            ->handle(app(FonnteService::class));

        // Pemilik UMKM kecil jauh lebih sering membuka WhatsApp daripada
        // email, dan hasil verifikasi termasuk kabar yang mereka tunggu.
        Mail::assertNothingSent();
    });
});
