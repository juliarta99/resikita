<?php

declare(strict_types=1);

namespace App\Livewire\BankSampah;

use App\Livewire\Concerns\MemberiUmpanBalik;
use App\Models\BankSampahHarga;
use App\Models\SetoranSampah;
use App\Models\SetoranSampahItem;
use App\Services\Wallet\SetoranService;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Loket setoran sampah.
 *
 * Alurnya mengikuti apa yang benar-benar terjadi di meja: nasabah
 * dikenali lewat kode QR, sampahnya ditimbang jenis per jenis, lalu
 * transaksi ditutup sekali di akhir.
 *
 * Saldo nasabah baru bertambah pada langkah terakhir, dan itu disengaja.
 * Menambahkan saldo per jenis akan meninggalkan setengah transaksi di
 * dompet warga kalau timbangan macet atau petugas keliru dan harus
 * membatalkan. Semua penambahan saldo dan pencatatan mutasi terjadi di
 * SetoranService, di dalam satu transaksi basis data.
 */
#[Title('Catat Setoran')]
class Setoran extends Component
{
    use MemberiUmpanBalik;

    #[Url(as: 'setoran', except: null)]
    public ?int $setoranId = null;

    /** Kode QR yang dipindai atau diketik petugas. */
    public string $kodeQr = '';

    public string $hargaId = '';

    public string $berat = '';

    public string $catatan = '';

    // ----------------------------------------------------------------
    // Membuka transaksi
    // ----------------------------------------------------------------

    public function cariNasabah(SetoranService $service): void
    {
        $this->validate(
            ['kodeQr' => ['required', 'string', 'size:26']],
            [
                'kodeQr.required' => 'Pindai atau ketik kode QR nasabah lebih dulu.',
                'kodeQr.size' => 'Kode QR nasabah terdiri dari 26 karakter.',
            ],
        );

        $bankSampah = auth()->user()->bankSampah;

        if ($bankSampah === null) {
            $this->pesanGalat('Akun Anda belum terhubung ke unit bank sampah mana pun.');

            return;
        }

        $setoran = $this->jalankan(function () use ($service, $bankSampah): SetoranSampah {
            $nasabah = $service->cariNasabah(trim($this->kodeQr));

            return $service->mulai($bankSampah, auth()->user(), $nasabah);
        });

        if ($setoran !== null) {
            $this->setoranId = $setoran->id;
            $this->reset('kodeQr');
            $this->pesanSukses("Setoran dibuka untuk {$setoran->nasabah->name}.");
        }
    }

    // ----------------------------------------------------------------
    // Menimbang
    // ----------------------------------------------------------------

    public function tambahItem(SetoranService $service): void
    {
        $setoran = $this->setoranBerjalan();

        if ($setoran === null) {
            return;
        }

        $this->validate(
            [
                'hargaId' => ['required', 'integer'],
                'berat' => ['required', 'numeric', 'gt:0', 'max:10000'],
            ],
            [
                'hargaId.required' => 'Pilih jenis sampah yang ditimbang.',
                'berat.gt' => 'Berat harus lebih dari nol.',
            ],
        );

        $harga = BankSampahHarga::query()
            ->where('bank_sampah_id', $setoran->bank_sampah_id)
            ->find((int) $this->hargaId);

        if ($harga === null) {
            $this->pesanGalat('Jenis sampah itu tidak ada di katalog unit Anda.');

            return;
        }

        $hasil = $this->jalankan(fn () => $service->tambahItem($setoran, $harga, (float) $this->berat));

        if ($hasil !== null) {
            $this->reset(['hargaId', 'berat']);
        }
    }

    public function hapusItem(int $itemId, SetoranService $service): void
    {
        $setoran = $this->setoranBerjalan();

        if ($setoran === null) {
            return;
        }

        $item = SetoranSampahItem::find($itemId);

        if ($item === null) {
            return;
        }

        $this->jalankan(
            fn () => $service->hapusItem($setoran, $item),
            'Item timbangan dihapus.',
        );
    }

    // ----------------------------------------------------------------
    // Menutup transaksi
    // ----------------------------------------------------------------

    public function selesaikan(SetoranService $service): void
    {
        $setoran = $this->setoranBerjalan();

        if ($setoran === null) {
            return;
        }

        if ($this->catatan !== '') {
            $setoran->update(['catatan' => $this->catatan]);
        }

        $hasil = $this->jalankan(
            fn () => $service->selesaikan($setoran),
            'Setoran selesai. Saldo nasabah sudah bertambah.',
        );

        if ($hasil !== null) {
            $this->reset(['setoranId', 'catatan', 'hargaId', 'berat']);
        }
    }

    public function batalkan(SetoranService $service): void
    {
        $setoran = $this->setoranBerjalan();

        if ($setoran === null) {
            return;
        }

        $hasil = $this->jalankan(
            fn () => $service->batalkan($setoran, 'Dibatalkan petugas dari panel web.'),
            'Setoran dibatalkan. Tidak ada saldo yang berpindah.',
        );

        if ($hasil !== null) {
            $this->reset(['setoranId', 'catatan', 'hargaId', 'berat']);
        }
    }

    /**
     * Setoran yang sedang dikerjakan, kalau memang milik unit ini.
     *
     * Pemeriksaan `bank_sampah_id` dilakukan di sini, bukan dipercayakan
     * kepada id di URL. Tanpa itu, mengganti angka pada alamat halaman
     * cukup untuk menyunting transaksi unit lain.
     */
    private function setoranBerjalan(): ?SetoranSampah
    {
        if ($this->setoranId === null) {
            return null;
        }

        $setoran = SetoranSampah::query()
            ->where('bank_sampah_id', auth()->user()->bank_sampah_id)
            ->find($this->setoranId);

        if ($setoran === null) {
            $this->reset('setoranId');
            $this->pesanGalat('Transaksi setoran itu tidak ditemukan di unit Anda.');
        }

        return $setoran;
    }

    public function render()
    {
        $bankSampah = auth()->user()->bankSampah;
        $setoran = $this->setoranBerjalan();

        return view('livewire.bank-sampah.setoran', [
            'bankSampah' => $bankSampah,
            'setoran' => $setoran?->load(['nasabah', 'item']),
            'katalog' => $bankSampah === null
                ? collect()
                : $bankSampah->harga()->aktif()->orderBy('jenis_sampah')->get(),
        ]);
    }
}
