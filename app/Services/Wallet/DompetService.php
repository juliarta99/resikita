<?php

declare(strict_types=1);

namespace App\Services\Wallet;

use App\Enums\TipeTransaksiDompet;
use App\Exceptions\AturanBisnisException;
use App\Models\Dompet;
use App\Models\DompetTransaksi;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Satu-satunya pintu perubahan saldo warga.
 *
 * ## Tiga hal yang membuat saldo bisa dipercaya
 *
 * 1. **Baris dikunci sebelum dibaca.** Dua setoran yang masuk bersamaan
 *    tanpa `lockForUpdate` akan sama-sama membaca saldo lama, lalu
 *    saling menimpa, dan salah satunya hilang tanpa jejak.
 *
 * 2. **Saldo sebelum dan sesudah dicatat per transaksi.** Riwayat jadi
 *    bisa diaudit baris demi baris. Satu baris yang keliru tidak
 *    menular ke seluruh riwayat seperti kalau saldo hanya dihitung dari
 *    penjumlahan.
 *
 * 3. **Semua nilai integer rupiah.** Skema lama memakai decimal(14,2);
 *    saldo bank sampah justru jenis nilai yang paling sering
 *    ditambah-kurang, dan di situlah galat pembulatan mengumpul.
 *
 * Kolom `dompet.saldo` tidak pernah diubah dari tempat lain.
 */
class DompetService
{
    /** Dompet milik pengguna; dibuat kalau belum ada. */
    public function untuk(User $user): Dompet
    {
        return Dompet::firstOrCreate(['user_id' => $user->id], ['saldo' => 0]);
    }

    public function saldo(User $user): int
    {
        return $this->untuk($user)->saldo;
    }

    /** Tambah saldo: setoran sampah atau pengembalian dana. */
    public function kredit(
        User $user,
        int $jumlah,
        TipeTransaksiDompet $tipe = TipeTransaksiDompet::Setor,
        ?Model $reference = null,
        ?string $keterangan = null,
    ): DompetTransaksi {
        if (! $tipe->isPemasukan()) {
            throw AturanBisnisException::karena(
                "Tipe transaksi \"{$tipe->label()}\" bukan pemasukan.",
            );
        }

        return $this->terapkan($user, $jumlah, $tipe, $reference, $keterangan);
    }

    /** Kurangi saldo: belanja, penarikan, atau iuran TPS. */
    public function debit(
        User $user,
        int $jumlah,
        TipeTransaksiDompet $tipe = TipeTransaksiDompet::Belanja,
        ?Model $reference = null,
        ?string $keterangan = null,
    ): DompetTransaksi {
        if ($tipe->isPemasukan()) {
            throw AturanBisnisException::karena(
                "Tipe transaksi \"{$tipe->label()}\" bukan pengeluaran.",
            );
        }

        return $this->terapkan($user, $jumlah, $tipe, $reference, $keterangan);
    }

    /** Apakah saldo cukup untuk sebuah pengeluaran. */
    public function cukup(User $user, int $jumlah): bool
    {
        return $this->saldo($user) >= $jumlah;
    }

    /**
     * Mutasi saldo, terurut dari yang terbaru.
     *
     * @return Builder<DompetTransaksi>
     */
    public function mutasi(User $user)
    {
        return DompetTransaksi::query()
            ->where('dompet_id', $this->untuk($user)->id)
            ->latest('id');
    }

    /**
     * Ringkasan pemasukan dan pengeluaran pada rentang waktu.
     *
     * @return array{masuk: int, keluar: int, saldo: int}
     */
    public function ringkasan(User $user, ?string $sejak = null): array
    {
        $query = DompetTransaksi::query()->where('dompet_id', $this->untuk($user)->id);

        if ($sejak !== null) {
            $query->where('created_at', '>=', $sejak);
        }

        $pemasukan = array_map(
            static fn (TipeTransaksiDompet $t): string => $t->value,
            array_filter(TipeTransaksiDompet::cases(), static fn (TipeTransaksiDompet $t): bool => $t->isPemasukan()),
        );

        $agregat = (clone $query)
            ->selectRaw('sum(case when tipe in ("'.implode('","', $pemasukan).'") then jumlah else 0 end) as masuk')
            ->selectRaw('sum(case when tipe not in ("'.implode('","', $pemasukan).'") then jumlah else 0 end) as keluar')
            ->first();

        return [
            'masuk' => (int) ($agregat->masuk ?? 0),
            'keluar' => (int) ($agregat->keluar ?? 0),
            'saldo' => $this->saldo($user),
        ];
    }

    /**
     * Terapkan mutasi di dalam transaction dengan baris terkunci.
     *
     * `$jumlah` selalu diperlakukan sebagai nilai positif; arahnya
     * ditentukan oleh tipe transaksi, bukan oleh tanda yang dikirim
     * pemanggil. Dengan begitu tidak mungkin ada pemanggil yang
     * mengirim nilai negatif untuk sebuah kredit dan diam-diam
     * mengurangi saldo.
     */
    private function terapkan(
        User $user,
        int $jumlah,
        TipeTransaksiDompet $tipe,
        ?Model $reference,
        ?string $keterangan,
    ): DompetTransaksi {
        $jumlah = abs($jumlah);

        if ($jumlah === 0) {
            throw AturanBisnisException::karena('Jumlah transaksi tidak boleh nol.');
        }

        return DB::transaction(function () use ($user, $jumlah, $tipe, $reference, $keterangan): DompetTransaksi {
            $this->untuk($user);

            $dompet = Dompet::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $saldoSebelum = $dompet->saldo;
            $saldoSesudah = $tipe->terapkan($saldoSebelum, $jumlah);

            if ($saldoSesudah < 0) {
                throw AturanBisnisException::karena(sprintf(
                    'Saldo tidak mencukupi. Saldo Anda Rp %s, dibutuhkan Rp %s.',
                    number_format($saldoSebelum, 0, ',', '.'),
                    number_format($jumlah, 0, ',', '.'),
                ));
            }

            $dompet->update(['saldo' => $saldoSesudah]);

            return DompetTransaksi::create([
                'dompet_id' => $dompet->id,
                'tipe' => $tipe,
                'jumlah' => $jumlah,
                'saldo_sebelum' => $saldoSebelum,
                'saldo_sesudah' => $saldoSesudah,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'keterangan' => $keterangan,
            ]);
        });
    }
}
