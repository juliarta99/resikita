<?php

declare(strict_types=1);

namespace App\Services\Tps;

use App\Enums\MetodeBayar;
use App\Enums\StatusAktif;
use App\Enums\StatusIuran;
use App\Enums\StatusPembayaran;
use App\Enums\TipeTransaksiDompet;
use App\Exceptions\AturanBisnisException;
use App\Models\Pembayaran;
use App\Models\Tps;
use App\Models\TpsAnggota;
use App\Models\TpsIuran;
use App\Models\User;
use App\Services\Wallet\DompetService;
use Illuminate\Support\Facades\DB;

/**
 * Keanggotaan dan iuran TPS.
 *
 * TPS tetap ada sebagai entitas meski role `admin_tps` dihapus; kini
 * dikelola pemerintah wilayah yang membawahinya (CLAUDE.md 6.1).
 *
 * Iuran bisa dibayar dari saldo yang berasal dari setoran sampah. Itu
 * titik paling nyata dari klaim ekonomi sirkular: warga yang rajin
 * memilah bisa menutup retribusinya sendiri, tanpa uang tunai keluar
 * dari rumah tangganya.
 */
class TpsService
{
    public function __construct(
        private readonly DompetService $dompet,
    ) {}

    /** Daftarkan warga sebagai anggota sebuah TPS. */
    public function gabung(Tps $tps, User $user): TpsAnggota
    {
        $adaLain = TpsAnggota::query()
            ->where('user_id', $user->id)
            ->where('status', StatusAktif::Aktif)
            ->where('tps_id', '!=', $tps->id)
            ->exists();

        if ($adaLain) {
            throw AturanBisnisException::karena(
                'Anda masih terdaftar sebagai anggota TPS lain. Keluar dari keanggotaan tersebut lebih dulu.',
            );
        }

        $anggota = TpsAnggota::query()
            ->where('tps_id', $tps->id)
            ->where('user_id', $user->id)
            ->first();

        if ($anggota !== null) {
            if ($anggota->status === StatusAktif::Aktif) {
                throw AturanBisnisException::karena('Anda sudah terdaftar di TPS ini.');
            }

            // Keanggotaan lama diaktifkan kembali, bukan dibuat ulang,
            // supaya riwayat iuran warga tidak terputus.
            $anggota->update([
                'status' => StatusAktif::Aktif,
                'bergabung_at' => now(),
            ]);

            return $anggota->fresh();
        }

        return TpsAnggota::create([
            'tps_id' => $tps->id,
            'user_id' => $user->id,
            'status' => StatusAktif::Aktif,
            'bergabung_at' => now(),
        ]);
    }

    public function keluar(TpsAnggota $anggota): TpsAnggota
    {
        if ($anggota->iuran()->belumLunas()->exists()) {
            throw AturanBisnisException::karena(
                'Masih ada tagihan iuran yang belum lunas. Lunasi lebih dulu sebelum keluar dari keanggotaan.',
            );
        }

        $anggota->update(['status' => StatusAktif::Nonaktif]);

        return $anggota->fresh();
    }

    /** Keanggotaan aktif milik seorang warga. */
    public function keanggotaan(User $user): ?TpsAnggota
    {
        return TpsAnggota::query()
            ->where('user_id', $user->id)
            ->aktif()
            ->with('tps.wilayah')
            ->first();
    }

    /**
     * Terbitkan tagihan iuran untuk satu periode.
     *
     * Aman dipanggil berulang: unique `(tps_anggota_id, periode)` di
     * basis data menjamin satu tagihan per bulan, dan pemeriksaan di
     * sini membuatnya gagal dengan pesan yang jelas alih-alih galat SQL.
     */
    public function terbitkanTagihan(TpsAnggota $anggota, string $periode): TpsIuran
    {
        $tps = $anggota->tps;

        if (! $tps->is_berbayar || $tps->tarif_bulanan === null) {
            throw AturanBisnisException::karena('TPS ini tidak memungut iuran.');
        }

        $adaTagihan = TpsIuran::query()
            ->where('tps_anggota_id', $anggota->id)
            ->periode($periode)
            ->first();

        if ($adaTagihan !== null) {
            return $adaTagihan;
        }

        return TpsIuran::create([
            'tps_anggota_id' => $anggota->id,
            'periode' => $periode,
            'jumlah' => $tps->tarif_bulanan,
            'status' => StatusIuran::Menunggu,
        ]);
    }

    /**
     * Bayar iuran memakai saldo dompet.
     *
     * Pembayaran lewat Midtrans ditangani jalur pembayaran umum dan
     * dikonfirmasi lewat callback, bukan di sini.
     */
    public function bayarDenganSaldo(TpsIuran $iuran, User $user): TpsIuran
    {
        if (! $iuran->status->bisaDibayar()) {
            throw AturanBisnisException::karena('Tagihan ini sudah lunas.');
        }

        if ($iuran->anggota->user_id !== $user->id) {
            throw AturanBisnisException::tidakBerwenang('Tagihan ini bukan milik Anda.');
        }

        return DB::transaction(function () use ($iuran, $user): TpsIuran {
            $this->dompet->debit(
                $user,
                $iuran->jumlah,
                TipeTransaksiDompet::Iuran,
                $iuran,
                "Iuran TPS periode {$iuran->periode}",
            );

            $iuran->update([
                'status' => StatusIuran::Lunas,
                'metode_bayar' => MetodeBayar::Saldo,
                'dibayar_at' => now(),
            ]);

            Pembayaran::create([
                'payable_type' => $iuran->getMorphClass(),
                'payable_id' => $iuran->id,
                'metode' => MetodeBayar::Saldo->value,
                'jumlah' => $iuran->jumlah,
                'status' => StatusPembayaran::Paid,
                'dibayar_at' => now(),
            ]);

            return $iuran->fresh();
        });
    }
}
