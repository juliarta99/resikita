<?php

declare(strict_types=1);

namespace App\Services\Wallet;

use App\Enums\StatusSetoran;
use App\Enums\TipeTransaksiDompet;
use App\Exceptions\AturanBisnisException;
use App\Models\BankSampah;
use App\Models\BankSampahHarga;
use App\Models\SetoranSampah;
use App\Models\SetoranSampahItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Setoran sampah warga ke bank sampah.
 *
 * Ini simpul tempat sampah berubah menjadi daya beli, jadi dua hal
 * dijaga ketat:
 *
 * **Harga dibekukan saat transaksi.** Setiap item menyimpan
 * `jenis_snapshot` dan `harga_snapshot`. Bank sampah mengubah harga
 * cukup sering mengikuti pengepul; tanpa snapshot, menaikkan harga hari
 * ini akan diam-diam mengubah nilai setoran tahun lalu, dan riwayat
 * nasabah berhenti bisa dipercaya.
 *
 * **Saldo baru bertambah saat setoran diselesaikan.** Selama masih
 * `proses`, petugas masih menimbang dan menambah item. Mengkredit saldo
 * per item akan menampilkan angka yang naik-turun di layar nasabah, dan
 * membuat pembatalan jadi rumit.
 */
class SetoranService
{
    public function __construct(
        private readonly DompetService $dompet,
    ) {}

    /**
     * Cari nasabah lewat kode QR.
     *
     * Kode QR berisi ULID acak, bukan NIK. Kode yang terlihat orang lain
     * karena itu tidak membocorkan identitas kependudukan pemiliknya.
     */
    public function cariNasabah(string $kodeQr): User
    {
        $nasabah = User::where('kode_qr', $kodeQr)->first();

        if ($nasabah === null) {
            throw AturanBisnisException::karena('Kode QR nasabah tidak dikenali.', 404);
        }

        if (! $nasabah->is_active) {
            throw AturanBisnisException::karena('Akun nasabah ini sedang dinonaktifkan.');
        }

        return $nasabah;
    }

    /** Mulai transaksi setoran baru dalam keadaan masih ditimbang. */
    public function mulai(BankSampah $bankSampah, User $petugas, User $nasabah): SetoranSampah
    {
        if ($petugas->bank_sampah_id !== $bankSampah->id) {
            throw AturanBisnisException::tidakBerwenang(
                'Anda tidak terdaftar sebagai pengelola bank sampah ini.',
            );
        }

        if ($nasabah->is($petugas)) {
            throw AturanBisnisException::karena(
                'Petugas tidak dapat mencatat setoran atas namanya sendiri.',
            );
        }

        return SetoranSampah::create([
            'bank_sampah_id' => $bankSampah->id,
            'petugas_id' => $petugas->id,
            'nasabah_id' => $nasabah->id,
            'kode_setoran' => $this->kodeSetoran(),
            'total_berat' => 0,
            'total_nilai' => 0,
            'status' => StatusSetoran::Proses,
        ]);
    }

    /**
     * Tambahkan hasil timbangan satu jenis sampah.
     *
     * Subtotal dibulatkan ke rupiah penuh dengan pembulatan biasa.
     * Berat dalam kilogram berdesimal dikalikan harga per kilogram
     * hampir selalu menghasilkan pecahan; membiarkannya sebagai float
     * di kolom uang persis kesalahan yang dibereskan di skema ini.
     */
    public function tambahItem(SetoranSampah $setoran, BankSampahHarga $harga, float $berat): SetoranSampahItem
    {
        $this->pastikanMasihProses($setoran);

        if ($harga->bank_sampah_id !== $setoran->bank_sampah_id) {
            throw AturanBisnisException::karena(
                'Jenis sampah tersebut bukan bagian dari katalog bank sampah ini.',
            );
        }

        if (! $harga->is_active) {
            throw AturanBisnisException::karena(
                "Jenis \"{$harga->jenis_sampah}\" sedang tidak diterima.",
            );
        }

        if ($berat <= 0) {
            throw AturanBisnisException::karena('Berat setoran harus lebih dari nol.');
        }

        return DB::transaction(function () use ($setoran, $harga, $berat): SetoranSampahItem {
            $subtotal = (int) round($berat * $harga->harga_per_satuan);

            $item = SetoranSampahItem::create([
                'setoran_id' => $setoran->id,
                'harga_id' => $harga->id,
                'jenis_snapshot' => $harga->jenis_sampah,
                'berat' => $berat,
                'harga_snapshot' => $harga->harga_per_satuan,
                'subtotal' => $subtotal,
            ]);

            $this->hitungUlangTotal($setoran);

            return $item;
        });
    }

    /** Hapus satu item yang salah timbang. */
    public function hapusItem(SetoranSampah $setoran, SetoranSampahItem $item): void
    {
        $this->pastikanMasihProses($setoran);

        if ($item->setoran_id !== $setoran->id) {
            throw AturanBisnisException::karena('Item tersebut bukan bagian dari setoran ini.');
        }

        DB::transaction(function () use ($setoran, $item): void {
            $item->delete();
            $this->hitungUlangTotal($setoran);
        });
    }

    /**
     * Tutup setoran dan kreditkan nilainya ke saldo nasabah.
     */
    public function selesaikan(SetoranSampah $setoran): SetoranSampah
    {
        $this->pastikanMasihProses($setoran);

        if ($setoran->item()->count() === 0) {
            throw AturanBisnisException::karena('Setoran tanpa item tidak bisa diselesaikan.');
        }

        return DB::transaction(function () use ($setoran): SetoranSampah {
            $this->hitungUlangTotal($setoran);
            $setoran->refresh();

            $this->dompet->kredit(
                $setoran->nasabah,
                $setoran->total_nilai,
                TipeTransaksiDompet::Setor,
                $setoran,
                sprintf(
                    'Setoran %s di %s (%s kg)',
                    $setoran->kode_setoran,
                    $setoran->bankSampah->nama,
                    rtrim(rtrim(number_format((float) $setoran->total_berat, 2, ',', '.'), '0'), ','),
                ),
            );

            $setoran->update(['status' => StatusSetoran::Selesai]);

            return $setoran->fresh();
        });
    }

    /**
     * Batalkan setoran yang belum selesai.
     *
     * Setoran yang sudah selesai tidak bisa dibatalkan lewat jalur ini,
     * saldonya sudah masuk dan mungkin sudah dibelanjakan. Koreksi atas
     * setoran yang sudah tutup harus berupa transaksi penyeimbang
     * tersendiri agar riwayat tetap utuh.
     */
    public function batalkan(SetoranSampah $setoran, ?string $alasan = null): SetoranSampah
    {
        $this->pastikanMasihProses($setoran);

        $setoran->update([
            'status' => StatusSetoran::Batal,
            'catatan' => $alasan,
        ]);

        return $setoran->fresh();
    }

    /**
     * Rekap volume dan nilai sebuah bank sampah.
     *
     * @return array{jumlah_transaksi: int, total_berat: float, total_nilai: int, jumlah_nasabah: int}
     */
    public function rekap(BankSampah $bankSampah, ?string $sejak = null): array
    {
        $query = SetoranSampah::query()
            ->where('bank_sampah_id', $bankSampah->id)
            ->where('status', StatusSetoran::Selesai);

        if ($sejak !== null) {
            $query->where('created_at', '>=', $sejak);
        }

        $agregat = (clone $query)
            ->selectRaw('count(*) as jumlah, sum(total_berat) as berat, sum(total_nilai) as nilai')
            ->first();

        return [
            'jumlah_transaksi' => (int) ($agregat->jumlah ?? 0),
            'total_berat' => (float) ($agregat->berat ?? 0),
            'total_nilai' => (int) ($agregat->nilai ?? 0),
            'jumlah_nasabah' => (clone $query)->distinct('nasabah_id')->count('nasabah_id'),
        ];
    }

    /** Hitung ulang total dari item, bukan dengan menambah secara bertahap. */
    private function hitungUlangTotal(SetoranSampah $setoran): void
    {
        $agregat = SetoranSampahItem::query()
            ->where('setoran_id', $setoran->id)
            ->selectRaw('sum(berat) as berat, sum(subtotal) as nilai')
            ->first();

        $setoran->update([
            'total_berat' => (float) ($agregat->berat ?? 0),
            'total_nilai' => (int) ($agregat->nilai ?? 0),
        ]);
    }

    private function pastikanMasihProses(SetoranSampah $setoran): void
    {
        if ($setoran->status !== StatusSetoran::Proses) {
            throw AturanBisnisException::karena(
                "Setoran ini sudah {$setoran->status->label()} dan tidak bisa diubah lagi.",
            );
        }
    }

    private function kodeSetoran(): string
    {
        return 'STR-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
    }
}
