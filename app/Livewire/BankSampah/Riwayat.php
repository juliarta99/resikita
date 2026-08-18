<?php

declare(strict_types=1);

namespace App\Livewire\BankSampah;

use App\Enums\StatusSetoran;
use App\Models\SetoranSampah;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Riwayat transaksi setoran unit ini.
 *
 * Dipakai untuk mencocokkan pembukuan dan menelusuri keberatan nasabah.
 * Karena itu setiap baris bisa dibuka sampai ke rincian timbangannya,
 * lengkap dengan harga yang berlaku saat itu, bukan harga hari ini.
 */
#[Title('Riwayat Setoran')]
class Riwayat extends Component
{
    use WithPagination;

    #[Url(as: 'status', except: '')]
    public string $status = '';

    #[Url(as: 'q', except: '')]
    public string $cari = '';

    public ?int $rincianId = null;

    public function updated(string $properti): void
    {
        if ($properti !== 'page') {
            $this->resetPage();
        }
    }

    public function lihatRincian(int $id): void
    {
        $this->rincianId = $this->rincianId === $id ? null : $id;
    }

    public function render()
    {
        $bankSampahId = auth()->user()->bank_sampah_id;

        if ($bankSampahId === null) {
            return view('livewire.bank-sampah.riwayat', ['daftar' => null]);
        }

        $query = SetoranSampah::query()
            ->where('bank_sampah_id', $bankSampahId)
            ->with(['nasabah:id,name,email', 'petugas:id,name'])
            ->withCount('item')
            ->latest('id');

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        if (trim($this->cari) !== '') {
            $kata = trim($this->cari);
            $query->where(fn ($q) => $q
                ->where('kode_setoran', 'like', "%{$kata}%")
                ->orWhereHas('nasabah', fn ($n) => $n->where('name', 'like', "%{$kata}%")));
        }

        return view('livewire.bank-sampah.riwayat', [
            'daftar' => $query->paginate(15),
            'statusTersedia' => StatusSetoran::options(),
            'rincian' => $this->rincianId !== null
                ? SetoranSampah::query()
                    ->where('bank_sampah_id', $bankSampahId)
                    ->with('item')
                    ->find($this->rincianId)
                : null,
        ]);
    }
}
