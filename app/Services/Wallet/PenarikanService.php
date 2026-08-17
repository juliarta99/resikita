<?php

declare(strict_types=1);

namespace App\Services\Wallet;

use App\Enums\StatusPenarikan;
use App\Enums\TipeTransaksiDompet;
use App\Exceptions\AturanBisnisException;
use App\Models\PenarikanSaldo;
use App\Models\Umkm;
use App\Models\UmkmPenarikan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Penarikan saldo ke rekening bank, untuk warga maupun UMKM.
 *
 * ## Saldo dipotong saat pengajuan, bukan saat pencairan
 *
 * Ini keputusan yang paling menentukan di kelas ini. Kalau saldo baru
 * dipotong ketika admin menyetujui, seorang pengguna bisa mengajukan
 * penarikan seluruh saldonya lalu membelanjakannya sambil menunggu
 * persetujuan, dan admin menyetujui pencairan atas uang yang sudah
 * tidak ada.
 *
 * Karena dipotong di muka, penolakan wajib mengembalikannya. Itu
 * ditegakkan oleh `StatusPenarikan::perluKembalikanSaldo()`.
 */
class PenarikanService
{
    public function __construct(
        private readonly DompetService $dompet,
        private readonly UmkmDompetService $umkmDompet,
    ) {}

    /**
     * Ajukan penarikan saldo warga.
     *
     * @param  array<string, mixed>  $data
     */
    public function ajukan(User $user, array $data): PenarikanSaldo
    {
        $jumlah = (int) $data['jumlah'];

        $this->pastikanJumlahWajar($jumlah);

        if (! $this->dompet->cukup($user, $jumlah)) {
            throw AturanBisnisException::karena(sprintf(
                'Saldo tidak mencukupi. Saldo Anda Rp %s.',
                number_format($this->dompet->saldo($user), 0, ',', '.'),
            ));
        }

        if (PenarikanSaldo::query()->where('user_id', $user->id)->menunggu()->exists()) {
            throw AturanBisnisException::karena(
                'Masih ada pengajuan penarikan yang menunggu persetujuan. Tunggu sampai selesai sebelum mengajukan lagi.',
            );
        }

        return DB::transaction(function () use ($user, $data, $jumlah): PenarikanSaldo {
            $penarikan = PenarikanSaldo::create([
                'user_id' => $user->id,
                'jumlah' => $jumlah,
                'metode' => $data['metode'] ?? 'transfer_bank',
                'nama_bank' => $data['nama_bank'] ?? null,
                'no_rekening' => $data['no_rekening'],
                'atas_nama' => $data['atas_nama'],
                'status' => StatusPenarikan::Menunggu,
            ]);

            $this->dompet->debit(
                $user,
                $jumlah,
                TipeTransaksiDompet::Penarikan,
                $penarikan,
                'Pengajuan penarikan saldo',
            );

            return $penarikan;
        });
    }

    /** Setujui pengajuan; dana dianggap sedang ditransfer. */
    public function setujui(PenarikanSaldo $penarikan, User $penyetuju): PenarikanSaldo
    {
        $this->pastikanMasihMenunggu($penarikan);

        $penarikan->update([
            'status' => StatusPenarikan::Disetujui,
            'disetujui_oleh' => $penyetuju->id,
        ]);

        return $penarikan->fresh();
    }

    /** Tandai dana sudah benar-benar ditransfer. */
    public function tandaiSelesai(PenarikanSaldo $penarikan, ?string $catatan = null): PenarikanSaldo
    {
        if ($penarikan->status !== StatusPenarikan::Disetujui) {
            throw AturanBisnisException::karena(
                'Hanya penarikan yang sudah disetujui yang bisa ditandai selesai.',
            );
        }

        $penarikan->update([
            'status' => StatusPenarikan::Selesai,
            'catatan' => $catatan ?? $penarikan->catatan,
        ]);

        return $penarikan->fresh();
    }

    /**
     * Tolak pengajuan dan kembalikan saldo yang sudah dipotong.
     */
    public function tolak(PenarikanSaldo $penarikan, User $penolak, string $alasan): PenarikanSaldo
    {
        $this->pastikanMasihMenunggu($penarikan);

        if (trim($alasan) === '') {
            throw AturanBisnisException::karena('Alasan penolakan wajib diisi.');
        }

        return DB::transaction(function () use ($penarikan, $penolak, $alasan): PenarikanSaldo {
            $penarikan->update([
                'status' => StatusPenarikan::Ditolak,
                'disetujui_oleh' => $penolak->id,
                'catatan' => $alasan,
            ]);

            if ($penarikan->status->perluKembalikanSaldo()) {
                $this->dompet->kredit(
                    $penarikan->user,
                    $penarikan->jumlah,
                    TipeTransaksiDompet::Refund,
                    $penarikan,
                    'Pengembalian saldo, penarikan ditolak',
                );
            }

            return $penarikan->fresh();
        });
    }

    /**
     * Ajukan penarikan saldo UMKM.
     *
     * @param  array<string, mixed>  $data
     */
    public function ajukanUmkm(Umkm $umkm, array $data): UmkmPenarikan
    {
        $jumlah = (int) $data['jumlah'];

        $this->pastikanJumlahWajar($jumlah);

        if (! $this->umkmDompet->cukup($umkm, $jumlah)) {
            throw AturanBisnisException::karena(sprintf(
                'Saldo tidak mencukupi. Saldo UMKM Rp %s.',
                number_format($this->umkmDompet->saldo($umkm), 0, ',', '.'),
            ));
        }

        if (UmkmPenarikan::query()->where('umkm_id', $umkm->id)->menunggu()->exists()) {
            throw AturanBisnisException::karena(
                'Masih ada pengajuan penarikan yang menunggu persetujuan.',
            );
        }

        return DB::transaction(function () use ($umkm, $data, $jumlah): UmkmPenarikan {
            $penarikan = UmkmPenarikan::create([
                'umkm_id' => $umkm->id,
                'jumlah' => $jumlah,
                'metode' => $data['metode'] ?? 'transfer_bank',
                'nama_bank' => $data['nama_bank'] ?? null,
                'no_rekening' => $data['no_rekening'],
                'atas_nama' => $data['atas_nama'],
                'status' => StatusPenarikan::Menunggu,
            ]);

            $this->umkmDompet->debit(
                $umkm,
                $jumlah,
                TipeTransaksiDompet::Penarikan,
                $penarikan,
                'Pengajuan penarikan saldo UMKM',
            );

            return $penarikan;
        });
    }

    public function setujuiUmkm(UmkmPenarikan $penarikan, User $penyetuju): UmkmPenarikan
    {
        $this->pastikanMasihMenunggu($penarikan);

        $penarikan->update([
            'status' => StatusPenarikan::Disetujui,
            'disetujui_oleh' => $penyetuju->id,
        ]);

        return $penarikan->fresh();
    }

    public function tolakUmkm(UmkmPenarikan $penarikan, User $penolak, string $alasan): UmkmPenarikan
    {
        $this->pastikanMasihMenunggu($penarikan);

        if (trim($alasan) === '') {
            throw AturanBisnisException::karena('Alasan penolakan wajib diisi.');
        }

        return DB::transaction(function () use ($penarikan, $penolak, $alasan): UmkmPenarikan {
            $penarikan->update([
                'status' => StatusPenarikan::Ditolak,
                'disetujui_oleh' => $penolak->id,
                'catatan' => $alasan,
            ]);

            $this->umkmDompet->kredit(
                $penarikan->umkm,
                $penarikan->jumlah,
                TipeTransaksiDompet::Refund,
                $penarikan,
                'Pengembalian saldo, penarikan ditolak',
            );

            return $penarikan->fresh();
        });
    }

    private function pastikanJumlahWajar(int $jumlah): void
    {
        $minimum = (int) config('resikita.dompet.penarikan_minimum', 10_000);
        $maksimum = (int) config('resikita.dompet.penarikan_maksimum', 10_000_000);

        if ($jumlah < $minimum) {
            throw AturanBisnisException::karena(sprintf(
                'Penarikan minimum Rp %s.',
                number_format($minimum, 0, ',', '.'),
            ));
        }

        if ($jumlah > $maksimum) {
            throw AturanBisnisException::karena(sprintf(
                'Penarikan maksimum Rp %s per pengajuan.',
                number_format($maksimum, 0, ',', '.'),
            ));
        }
    }

    private function pastikanMasihMenunggu(PenarikanSaldo|UmkmPenarikan $penarikan): void
    {
        if ($penarikan->status !== StatusPenarikan::Menunggu) {
            throw AturanBisnisException::karena(
                "Pengajuan ini sudah {$penarikan->status->label()}.",
            );
        }
    }
}
