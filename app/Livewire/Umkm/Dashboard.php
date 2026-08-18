<?php

declare(strict_types=1);

namespace App\Livewire\Umkm;

use App\Enums\StatusPesanan;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Services\Wallet\UmkmDompetService;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Dasbor UMKM.
 *
 * Angka yang ditampilkan dipilih dari sudut pandang penjual: pesanan
 * yang menunggu dikerjakan hari ini, saldo yang bisa ditarik, dan stok
 * yang hampir habis. Total penjualan sepanjang masa memang enak dilihat,
 * tapi tidak memberi tahu apa yang harus dilakukan pagi ini.
 */
#[Title('Dasbor UMKM')]
class Dashboard extends Component
{
    public function render(UmkmDompetService $dompet)
    {
        $umkm = auth()->user()->umkm;

        if ($umkm === null) {
            return view('livewire.umkm.dashboard', ['umkm' => null]);
        }

        $pesanan = fn () => Pesanan::query()->where('umkm_id', $umkm->id);

        return view('livewire.umkm.dashboard', [
            'umkm' => $umkm,
            'saldo' => $dompet->saldo($umkm),

            'perluDikemas' => $pesanan()->where('status', StatusPesanan::Dibayar)->count(),
            'sedangDikirim' => $pesanan()->where('status', StatusPesanan::Dikirim)->count(),
            'selesaiBulanIni' => $pesanan()
                ->where('status', StatusPesanan::Selesai)
                ->where('selesai_at', '>=', now()->startOfMonth())
                ->count(),
            'pendapatanBulanIni' => (int) $pesanan()
                ->where('status', StatusPesanan::Selesai)
                ->where('selesai_at', '>=', now()->startOfMonth())
                ->sum('total'),

            'antrean' => $pesanan()
                ->whereIn('status', [StatusPesanan::Dibayar, StatusPesanan::Dikemas])
                ->untukDaftar()
                ->oldest('dibayar_at')
                ->limit(6)
                ->get(),

            'stokMenipis' => Produk::query()
                ->where('umkm_id', $umkm->id)
                ->aktif()
                ->where('stok', '<=', 5)
                ->orderBy('stok')
                ->limit(6)
                ->get(),

            'jumlahProduk' => Produk::query()->where('umkm_id', $umkm->id)->aktif()->count(),
        ]);
    }
}
