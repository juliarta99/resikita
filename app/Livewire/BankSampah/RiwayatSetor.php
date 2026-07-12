<?php

namespace App\Livewire\BankSampah;

use App\Models\User;
use App\Models\WasteDeposit;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.banksampah')]
class RiwayatSetor extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    /** Pengguna login sebagai instance User (untuk bantu type-hint). */
    private function me(): User
    {
        /** @var \App\Models\User $u */
        $u = Auth::user();

        return $u;
    }

    private function baseQuery()
    {
        $me = $this->me();

        $q = WasteDeposit::with('nasabah', 'petugas')
            ->where('bank_sampah_id', $me->bank_sampah_id)
            ->latest();

        // Petugas hanya melihat setoran yang ia proses sendiri
        if ($me->hasRole('petugas_bank_sampah') && ! $me->hasRole('admin_bank_sampah')) {
            $q->where('petugas_id', $me->id);
        }

        if ($this->search !== '') {
            $s = $this->search;
            $q->whereHas('nasabah', fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('kode_qr', 'like', "%{$s}%"));
        }

        return $q;
    }

    public function export(): StreamedResponse
    {
        abort_unless($this->me()->hasRole('admin_bank_sampah'), 403);

        $rows = $this->baseQuery()->get();
        $filename = 'riwayat-setor-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Tanggal', 'Nasabah', 'Kode QR', 'Petugas', 'Berat (kg)', 'Nilai (Rp)', 'Status']);
            foreach ($rows as $d) {
                fputcsv($out, [
                    $d->created_at->format('Y-m-d H:i'),
                    $d->nasabah?->name,
                    $d->nasabah?->kode_qr,
                    $d->petugas?->name,
                    $d->total_berat,
                    $d->total_nilai,
                    $d->status,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        return view('livewire.bank-sampah.riwayat-setor', [
            'daftar'  => $this->baseQuery()->paginate(15),
            'isAdmin' => $this->me()->hasRole('admin_bank_sampah'),
        ]);
    }
}