<?php

declare(strict_types=1);

namespace App\Livewire\BankSampah;

use App\Enums\StatusSetoran;
use App\Models\SetoranSampah;
use App\Services\Wallet\SetoranService;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Dasbor pengelola bank sampah.
 *
 * Menampilkan rekap volume dan nilai unit ini saja. Seluruh query
 * dikunci pada `users.bank_sampah_id`, satu unit tidak pernah melihat
 * transaksi unit lain, termasuk yang berada di desa yang sama.
 */
#[Title('Dasbor Bank Sampah')]
class Dashboard extends Component
{
    public int $rentang = 30;

    public function ubahRentang(int $hari): void
    {
        $this->rentang = in_array($hari, [7, 30, 90, 365], true) ? $hari : 30;
    }

    public function render(SetoranService $setoran)
    {
        $bankSampah = auth()->user()->bankSampah;

        if ($bankSampah === null) {
            return view('livewire.bank-sampah.dashboard', ['bankSampah' => null]);
        }

        $sejak = now()->subDays($this->rentang)->toDateTimeString();

        return view('livewire.bank-sampah.dashboard', [
            'bankSampah' => $bankSampah,
            'rekap' => $setoran->rekap($bankSampah, $sejak),
            'rekapTotal' => $setoran->rekap($bankSampah),

            'sedangProses' => SetoranSampah::query()
                ->where('bank_sampah_id', $bankSampah->id)
                ->where('status', StatusSetoran::Proses)
                ->with('nasabah:id,name')
                ->latest('id')
                ->get(),

            'terakhir' => SetoranSampah::query()
                ->where('bank_sampah_id', $bankSampah->id)
                ->where('status', StatusSetoran::Selesai)
                ->with('nasabah:id,name')
                ->latest('id')
                ->limit(8)
                ->get(),

            'jumlahHarga' => $bankSampah->harga()->aktif()->count(),
        ]);
    }
}
