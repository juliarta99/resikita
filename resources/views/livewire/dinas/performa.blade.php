<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-primary-900">Performa Dinas</h1>
        <p class="mt-1 text-sm text-gray-500">Statistik penanganan laporan pada rentang waktu tertentu.</p>
    </div>

    {{-- Filter + export --}}
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end">
            <div class="grid grid-cols-2 gap-3 sm:flex sm:gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-500">Dari</label>
                    <input type="date" wire:model.live="dari" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500">Sampai</label>
                    <input type="date" wire:model.live="sampai" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                </div>
            </div>
            <div class="flex flex-col gap-2 sm:ml-auto sm:flex-row">
                <button wire:click="exportKategori" class="rounded-lg border border-primary-500 px-4 py-2 text-sm font-semibold text-primary-500 hover:bg-primary-50">Export Kategori</button>
                <button wire:click="exportPetugas" class="rounded-lg border border-primary-500 px-4 py-2 text-sm font-semibold text-primary-500 hover:bg-primary-50">Export Petugas</button>
            </div>
        </div>
    </div>

    {{-- Statistik: 2 kolom HP, 3 tablet, 6 desktop --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-6">
        @foreach ([
            ['Masuk', $stat['masuk'], 'text-primary-900'],
            ['Menunggu', $stat['menunggu'], 'text-amber-600'],
            ['Diproses', $stat['proses'], 'text-blue-600'],
            ['Selesai', $stat['selesai'], 'text-primary-700'],
            ['Ditolak', $stat['ditolak'], 'text-red-600'],
            ['Penyelesaian', $stat['rate'] . '%', 'text-primary-700'],
        ] as [$label, $val, $cls])
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs text-gray-400">{{ $label }}</p>
                <p class="mt-1 text-lg font-semibold {{ $cls }} sm:text-xl">{{ $val }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Laporan per Kategori --}}
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
            <p class="text-sm font-semibold text-primary-900">Laporan per Kategori</p>
            <div class="mt-4 space-y-3">
                @php $maxKat = $perKategori->max('total') ?: 1; @endphp
                @forelse ($perKategori as $k)
                    @php
                        $lebarVolume = round($k['total'] / $maxKat * 100);
                        $lebarSelesai = $k['total'] > 0 ? round($k['selesai'] / $k['total'] * 100) : 0;
                    @endphp
                    <div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="truncate pr-2 text-primary-900">{{ $k['nama'] }}</span>
                            <span class="shrink-0 text-gray-500">{{ $k['selesai'] }}/{{ $k['total'] }} selesai</span>
                        </div>
                        <div class="mt-1 h-2.5 rounded-full bg-gray-100">
                            {{-- Panjang = volume laporan; isi gelap = yang sudah selesai --}}
                            <div class="h-2.5 rounded-full bg-primary-100" style="width: {{ $lebarVolume }}%">
                                <div class="h-2.5 rounded-full bg-primary-500" style="width: {{ $lebarSelesai }}%"></div>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">Tidak ada data pada rentang ini.</p>
                @endforelse
            </div>
        </div>

        {{-- Kinerja Petugas Lapangan --}}
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
            <p class="text-sm font-semibold text-primary-900">Kinerja Petugas Lapangan</p>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full min-w-[320px] text-left text-sm">
                    <thead class="text-xs uppercase tracking-wider text-gray-400">
                        <tr>
                            <th class="py-2 font-semibold">Petugas</th>
                            <th class="py-2 text-right font-semibold">Ditugaskan</th>
                            <th class="py-2 text-right font-semibold">Selesai</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($perPetugas as $p)
                            <tr>
                                <td class="py-2 pr-2 text-primary-900">{{ $p['nama'] }}</td>
                                <td class="py-2 text-right text-gray-600">{{ $p['ditugaskan'] }}</td>
                                <td class="py-2 text-right font-medium text-primary-700">{{ $p['selesai'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-6 text-center text-gray-400">Tidak ada penugasan pada rentang ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>