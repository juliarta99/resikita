@php $awalan = \App\Support\Navigasi::awalanRoute(auth()->user()); @endphp

<div>
    <x-ui.kepala-halaman
        judul="Manajemen laporan"
        keterangan="Laporan warga yang jatuh di dalam cakupan kewenangan Anda.">
        <x-slot:aksi>
            <x-ui.tombol jenis="kedua" wire:click="ekspor" ikon="unduh"
                         wire:loading.attr="disabled" wire:target="ekspor">
                <span wire:loading.remove wire:target="ekspor">Unduh rekap</span>
                <span wire:loading wire:target="ekspor">Menyiapkan…</span>
            </x-ui.tombol>
        </x-slot:aksi>
    </x-ui.kepala-halaman>

    <x-ui.kartu padat class="mb-6">
        <div class="grid gap-3 p-5 sm:grid-cols-2 lg:grid-cols-4">
            <x-ui.bidang label="Cari" untuk="cari">
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <x-ui.ikon nama="cari" class="h-4 w-4"/>
                    </span>
                    <x-ui.isian id="cari" wire:model.live.debounce.400ms="cari" class="pl-9"
                                placeholder="Judul, tiket, atau alamat"/>
                </div>
            </x-ui.bidang>

            <x-ui.bidang label="Status" untuk="status">
                <x-ui.pilihan id="status" wire:model.live="status" kosong="Semua status"
                              :opsi="$statusTersedia" :disabled="$hanyaPerluTindakan"/>
            </x-ui.bidang>

            <x-ui.bidang label="Kategori" untuk="kategori">
                <x-ui.pilihan id="kategori" wire:model.live="kategoriId" kosong="Semua kategori"
                              :opsi="$kategoriTersedia->all()"/>
            </x-ui.bidang>

            <x-ui.bidang label="Penyaring cepat">
                <label class="flex h-[42px] items-center gap-2 rounded-xl border border-gray-300 px-3.5 text-sm text-gray-700">
                    <input type="checkbox" wire:model.live="hanyaPerluTindakan"
                           class="h-4 w-4 rounded border-gray-300 text-primary-500 focus:ring-primary-200">
                    Perlu tindakan saya
                </label>
            </x-ui.bidang>
        </div>

        @if ($cari !== '' || $status !== '' || $kategoriId !== '' || $hanyaPerluTindakan)
            <div class="flex items-center justify-between gap-3 border-t border-gray-100 px-5 py-3">
                <p class="text-xs text-gray-500">
                    {{ number_format($daftar->total()) }} laporan cocok dengan penyaring.
                </p>
                <x-ui.tombol jenis="polos" ukuran="kecil" wire:click="bersihkanFilter" ikon="silang">
                    Bersihkan penyaring
                </x-ui.tombol>
            </div>
        @endif
    </x-ui.kartu>

    <x-ui.kartu padat>
        <div wire:loading.class="opacity-50" wire:target="cari,status,kategoriId,hanyaPerluTindakan,gotoPage,previousPage,nextPage">
            @if ($daftar->isEmpty())
                <x-ui.kosong
                    judul="Tidak ada laporan"
                    pesan="Belum ada laporan yang cocok dengan penyaring ini di wilayah Anda."/>
            @else
                <x-ui.tabel :kepala="['Laporan', 'Kategori', 'Lokasi', 'Status', 'Masuk', '']">
                    @foreach ($daftar as $laporan)
                        <tr class="transition hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <p class="font-medium text-primary-900">{{ $laporan->judul }}</p>
                                <p class="mt-0.5 font-mono text-xs text-gray-500">{{ $laporan->tiket }}</p>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $laporan->kategori?->nama ?? '—' }}</td>
                            <td class="max-w-xs px-4 py-3 text-gray-600">
                                <p class="truncate">{{ $laporan->alamat ?? '—' }}</p>
                                @if ($laporan->desa)
                                    <p class="mt-0.5 truncate text-xs text-gray-400">{{ $laporan->desa->nama }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3"><x-ui.lencana :status="$laporan->status"/></td>
                            <td class="whitespace-nowrap px-4 py-3 text-xs text-gray-500">
                                {{ $laporan->created_at->translatedFormat('j M Y') }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <x-ui.tombol jenis="kedua" ukuran="kecil"
                                             :tautan="route($awalan.'laporan.detail', $laporan)">
                                    Tinjau
                                </x-ui.tombol>
                            </td>
                        </tr>
                    @endforeach
                </x-ui.tabel>

                <div class="border-t border-gray-100 px-5 py-3">
                    {{ $daftar->links() }}
                </div>
            @endif
        </div>
    </x-ui.kartu>
</div>
