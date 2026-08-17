<?php

declare(strict_types=1);

namespace App\Livewire\BankSampah;

use App\Enums\StatusSetoran;
use App\Models\SetoranSampah;
use App\Models\User;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Warga yang pernah menyetor di unit ini.
 *
 * Bukan daftar seluruh pengguna Resikita. Sebuah unit bank sampah tidak
 * punya kepentingan atas data warga yang tidak pernah berurusan
 * dengannya, dan menampilkannya akan mengubah panel operasional menjadi
 * direktori kependudukan.
 */
#[Title('Nasabah')]
class Nasabah extends Component
{
    use WithPagination;

    public string $cari = '';

    public function updatedCari(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $bankSampahId = auth()->user()->bank_sampah_id;

        if ($bankSampahId === null) {
            return view('livewire.bank-sampah.nasabah', ['daftar' => null]);
        }

        /*
         * Agregat dihitung di basis data, bukan dengan menarik seluruh
         * setoran ke PHP lalu menjumlahkannya. Satu unit yang sudah
         * berjalan setahun bisa punya puluhan ribu baris setoran.
         */
        $query = User::query()
            ->select('users.*')
            ->whereHas('setoran', fn ($q) => $q
                ->where('bank_sampah_id', $bankSampahId)
                ->where('status', StatusSetoran::Selesai))
            ->withCount(['setoran as jumlah_setoran' => fn ($q) => $q
                ->where('bank_sampah_id', $bankSampahId)
                ->where('status', StatusSetoran::Selesai)])
            ->withSum(['setoran as total_berat' => fn ($q) => $q
                ->where('bank_sampah_id', $bankSampahId)
                ->where('status', StatusSetoran::Selesai)], 'total_berat')
            ->withSum(['setoran as total_nilai' => fn ($q) => $q
                ->where('bank_sampah_id', $bankSampahId)
                ->where('status', StatusSetoran::Selesai)], 'total_nilai')
            ->withMax(['setoran as setoran_terakhir' => fn ($q) => $q
                ->where('bank_sampah_id', $bankSampahId)
                ->where('status', StatusSetoran::Selesai)], 'created_at')
            ->orderByDesc('total_nilai');

        if (trim($this->cari) !== '') {
            $kata = trim($this->cari);
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$kata}%")
                ->orWhere('email', 'like', "%{$kata}%")
                ->orWhere('kode_qr', $kata));
        }

        return view('livewire.bank-sampah.nasabah', [
            'daftar' => $query->paginate(15),
            'totalNasabah' => SetoranSampah::query()
                ->where('bank_sampah_id', $bankSampahId)
                ->where('status', StatusSetoran::Selesai)
                ->distinct('nasabah_id')
                ->count('nasabah_id'),
        ]);
    }
}
