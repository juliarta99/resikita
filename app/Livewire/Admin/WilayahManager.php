<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\StatusRegistrasiWilayah;
use App\Enums\TingkatWilayah;
use App\Models\Wilayah;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Penelusuran hierarki wilayah nasional.
 *
 * Menggantikan tiga halaman master data lama, kecamatan, kelurahan,
 * dan banjar dinas, yang hanya berlaku untuk Bali. Hierarki sekarang
 * berkode Kemendagri dan berlaku untuk seluruh Indonesia
 * (CLAUDE.md 4.1).
 *
 * Penelusuran dilakukan per tingkat, tidak sekaligus. Memuat seluruh
 * desa di Indonesia dalam satu halaman berarti puluhan ribu baris yang
 * tidak akan pernah dibaca satu per satu.
 */
#[Title('Wilayah')]
class WilayahManager extends Component
{
    use WithPagination;

    #[Url(as: 'induk', except: null)]
    public ?int $indukId = null;

    #[Url(as: 'q', except: '')]
    public string $cari = '';

    #[Url(as: 'status', except: '')]
    public string $status = '';

    public function updated(string $properti): void
    {
        if ($properti !== 'page') {
            $this->resetPage();
        }
    }

    public function masuk(int $wilayahId): void
    {
        $this->indukId = $wilayahId;
        $this->cari = '';
        $this->resetPage();
    }

    public function keAtas(): void
    {
        $this->indukId = $this->indukId !== null
            ? Wilayah::find($this->indukId)?->parent_id
            : null;

        $this->resetPage();
    }

    public function render()
    {
        $induk = $this->indukId !== null ? Wilayah::with('parent.parent')->find($this->indukId) : null;

        $query = Wilayah::query()
            ->withCount('children')
            ->orderBy('nama');

        /*
         * Pencarian sengaja melintasi hierarki. Ketika seseorang mengetik
         * nama desa, ia tidak tahu, dan tidak perlu tahu, kabupaten
         * mana yang harus dibuka lebih dulu.
         */
        if (trim($this->cari) !== '') {
            $kata = trim($this->cari);
            $query->where(fn ($q) => $q->where('nama', 'like', "%{$kata}%")->orWhere('kode', 'like', "{$kata}%"));
        } else {
            $query->where('parent_id', $this->indukId);
        }

        if ($this->status !== '') {
            $query->where('status_registrasi', $this->status);
        }

        return view('livewire.admin.wilayah-manager', [
            'daftar' => $query->paginate(30),
            'induk' => $induk,
            'statusTersedia' => StatusRegistrasiWilayah::options(),
            'tingkatLabel' => TingkatWilayah::options(),
        ]);
    }
}
