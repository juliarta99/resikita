<?php

declare(strict_types=1);

namespace App\Livewire\Umkm;

use App\Livewire\Concerns\MemberiUmpanBalik;
use App\Models\UmkmPenarikan;
use App\Services\Wallet\PenarikanService;
use App\Services\Wallet\UmkmDompetService;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Saldo dan penarikan UMKM.
 *
 * Saldo langsung didebit saat pengajuan dibuat, bukan saat admin
 * menyetujui. Kalau tidak, penjual bisa mengajukan penarikan penuh
 * berkali-kali sebelum pengajuan pertamanya diproses. Aturan itu
 * ditegakkan PenarikanService di dalam satu transaksi basis data.
 */
#[Title('Saldo UMKM')]
class Saldo extends Component
{
    use MemberiUmpanBalik;
    use WithPagination;

    public bool $formTerbuka = false;

    public string $jumlah = '';

    public string $namaBank = '';

    public string $noRekening = '';

    public string $atasNama = '';

    public function bukaForm(): void
    {
        $this->resetValidation();
        $this->reset(['jumlah', 'namaBank', 'noRekening', 'atasNama']);

        $this->atasNama = auth()->user()->umkm?->nama ?? '';
        $this->formTerbuka = true;
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'jumlah' => ['required', 'integer', 'min:'.config('resikita.dompet.penarikan_minimum')],
            'namaBank' => ['required', 'string', 'max:100'],
            'noRekening' => ['required', 'string', 'max:50'],
            'atasNama' => ['required', 'string', 'max:150'],
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'jumlah' => 'jumlah penarikan', 'namaBank' => 'nama bank',
            'noRekening' => 'nomor rekening', 'atasNama' => 'atas nama',
        ];
    }

    public function ajukan(PenarikanService $service): void
    {
        $umkm = auth()->user()->umkm;

        if ($umkm === null) {
            $this->pesanGalat('Akun Anda belum terhubung ke UMKM mana pun.');

            return;
        }

        $this->validate();

        $hasil = $this->jalankan(
            fn () => $service->ajukanUmkm($umkm, [
                'jumlah' => (int) $this->jumlah,
                'nama_bank' => $this->namaBank,
                'no_rekening' => $this->noRekening,
                'atas_nama' => $this->atasNama,
            ]),
            'Pengajuan penarikan dikirim. Saldo sudah dipotong dan menunggu persetujuan admin.',
        );

        if ($hasil !== null) {
            $this->formTerbuka = false;
        }
    }

    public function render(UmkmDompetService $dompet)
    {
        $umkm = auth()->user()->umkm;

        if ($umkm === null) {
            return view('livewire.umkm.saldo', ['umkm' => null]);
        }

        return view('livewire.umkm.saldo', [
            'umkm' => $umkm,
            'saldo' => $dompet->saldo($umkm),
            'mutasi' => $dompet->mutasi($umkm)->paginate(10, pageName: 'mutasi'),
            'penarikan' => UmkmPenarikan::query()
                ->where('umkm_id', $umkm->id)
                ->latest('id')
                ->paginate(5, pageName: 'penarikan'),
            'minimum' => (int) config('resikita.dompet.penarikan_minimum'),
        ]);
    }
}
