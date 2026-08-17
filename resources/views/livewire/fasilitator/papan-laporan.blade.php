<div>
    <x-ui.kepala-halaman
        judul="Laporan belum terjangkau"
        keterangan="Laporan warga dari daerah yang belum punya pemerintah terdaftar di Resikita. Tanpa tindak lanjut, laporan ini berakhir sebagai data mati."/>

    <x-ui.kartu padat class="mb-6">
        <div class="grid gap-3 p-5 sm:grid-cols-3">
            <x-ui.bidang label="Cari" untuk="cari">
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <x-ui.ikon nama="cari" class="h-4 w-4"/>
                    </span>
                    <x-ui.isian id="cari" wire:model.live.debounce.400ms="cari" class="pl-9"
                                placeholder="Judul, tiket, atau alamat"/>
                </div>
            </x-ui.bidang>

            <x-ui.bidang label="Penyaring">
                <label class="flex h-[42px] items-center gap-2 rounded-xl border border-gray-300 px-3.5 text-sm text-gray-700">
                    <input type="checkbox" wire:model.live="hanyaBelumDitindaklanjuti"
                           class="h-4 w-4 rounded border-gray-300 text-primary-500 focus:ring-primary-200">
                    Belum ditindaklanjuti
                </label>
            </x-ui.bidang>

            <x-ui.bidang label="&nbsp;">
                <label class="flex h-[42px] items-center gap-2 rounded-xl border border-gray-300 px-3.5 text-sm text-gray-700">
                    <input type="checkbox" wire:model.live="hanyaMilikSaya"
                           class="h-4 w-4 rounded border-gray-300 text-primary-500 focus:ring-primary-200">
                    Ditugaskan kepada saya
                </label>
            </x-ui.bidang>
        </div>
    </x-ui.kartu>

    <x-ui.kartu padat>
        @if ($daftar->isEmpty())
            <x-ui.kosong
                ikon="centang"
                judul="Tidak ada laporan"
                pesan="Tidak ada laporan dari wilayah belum terjangkau yang cocok dengan penyaring ini."/>
        @else
            <x-ui.tabel :kepala="['Laporan', 'Wilayah', 'Tindak lanjut', 'Masuk', '']">
                @foreach ($daftar as $laporan)
                    <tr class="transition hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <p class="font-medium text-primary-900">{{ $laporan->judul }}</p>
                            <p class="mt-0.5 font-mono text-xs text-gray-500">{{ $laporan->tiket }}</p>
                        </td>
                        <td class="max-w-xs px-4 py-3 text-gray-600">
                            <p class="truncate">
                                {{ collect([$laporan->desa?->nama, $laporan->kabupaten?->nama])
                                    ->filter()->implode(', ') ?: 'Belum teridentifikasi' }}
                            </p>
                            @if ($laporan->alamat)
                                <p class="truncate text-xs text-gray-400">{{ $laporan->alamat }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($laporan->tindak_lanjut_count > 0)
                                <x-ui.lencana warna="hijau"
                                              :label="$laporan->tindak_lanjut_count.' catatan'"/>
                            @else
                                <x-ui.lencana warna="merah" label="Belum ada"/>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-xs text-gray-500">
                            {{ $laporan->created_at->translatedFormat('j M Y') }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            <x-ui.tombol jenis="kedua" ukuran="kecil"
                                         :tautan="route('fasilitator.laporan.detail', $laporan)">
                                Tindak lanjuti
                            </x-ui.tombol>
                        </td>
                    </tr>
                @endforeach
            </x-ui.tabel>

            <div class="border-t border-gray-100 px-5 py-3">{{ $daftar->links() }}</div>
        @endif
    </x-ui.kartu>
</div>
