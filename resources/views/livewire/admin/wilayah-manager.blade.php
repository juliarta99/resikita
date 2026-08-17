<div>
    <x-ui.kepala-halaman
        judul="Wilayah"
        keterangan="Hierarki administrasi nasional berkode Kemendagri: provinsi, kabupaten/kota, kecamatan, desa/kelurahan."/>

    {{-- Jejak navigasi --}}
    <nav aria-label="Jejak wilayah" class="mb-5 flex flex-wrap items-center gap-2 text-sm">
        <button type="button" wire:click="$set('indukId', null)"
                class="font-medium {{ $induk === null ? 'text-primary-900' : 'text-primary-700 hover:underline' }}">
            Seluruh Indonesia
        </button>

        @if ($induk?->parent?->parent)
            <span class="text-gray-300">/</span>
            <button type="button" wire:click="masuk({{ $induk->parent->parent->id }})"
                    class="text-primary-700 hover:underline">
                {{ $induk->parent->parent->namaLengkap() }}
            </button>
        @endif

        @if ($induk?->parent)
            <span class="text-gray-300">/</span>
            <button type="button" wire:click="masuk({{ $induk->parent->id }})"
                    class="text-primary-700 hover:underline">
                {{ $induk->parent->namaLengkap() }}
            </button>
        @endif

        @if ($induk)
            <span class="text-gray-300">/</span>
            <span class="font-medium text-primary-900">{{ $induk->namaLengkap() }}</span>
        @endif
    </nav>

    <div class="mb-5 grid gap-3 sm:grid-cols-2 lg:max-w-2xl">
        <x-ui.bidang label="Cari" untuk="cari-wilayah"
                     petunjuk="Pencarian melintasi seluruh tingkat, mengabaikan posisi jejak di atas.">
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                    <x-ui.ikon nama="cari" class="h-4 w-4"/>
                </span>
                <x-ui.isian id="cari-wilayah" wire:model.live.debounce.400ms="cari" class="pl-9"
                            placeholder="Nama atau kode wilayah"/>
            </div>
        </x-ui.bidang>

        <x-ui.bidang label="Status registrasi" untuk="status-wilayah">
            <x-ui.pilihan id="status-wilayah" wire:model.live="status" kosong="Semua status"
                          :opsi="$statusTersedia"/>
        </x-ui.bidang>
    </div>

    <x-ui.kartu padat>
        @if ($daftar->isEmpty())
            <x-ui.kosong
                ikon="peta"
                judul="Tidak ada wilayah"
                pesan="{{ $cari !== ''
                    ? 'Tidak ada wilayah yang cocok dengan pencarian ini.'
                    : 'Wilayah ini tidak punya turunan, atau data wilayah belum di-seed.' }}"/>
        @else
            <x-ui.tabel :kepala="['Nama', 'Kode', 'Tingkat', 'Status', 'Skor prioritas', '']">
                @foreach ($daftar as $wilayah)
                    <tr class="transition hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-primary-900">{{ $wilayah->nama }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $wilayah->kode }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $wilayah->tingkat->label() }}</td>
                        <td class="px-4 py-3"><x-ui.lencana :status="$wilayah->status_registrasi"/></td>
                        <td class="px-4 py-3 text-gray-600">
                            {{ $wilayah->skor_prioritas > 0 ? number_format($wilayah->skor_prioritas) : '—' }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            @if ($wilayah->children_count > 0)
                                <x-ui.tombol jenis="kedua" ukuran="kecil" wire:click="masuk({{ $wilayah->id }})">
                                    Buka {{ number_format($wilayah->children_count) }} turunan
                                </x-ui.tombol>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-ui.tabel>

            <div class="border-t border-gray-100 px-5 py-3">{{ $daftar->links() }}</div>
        @endif
    </x-ui.kartu>
</div>
