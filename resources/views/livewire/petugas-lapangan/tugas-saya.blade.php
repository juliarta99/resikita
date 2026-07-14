<div class="space-y-5">
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Tugas Saya</h1>
            <p class="text-slate-500 text-sm">Daftar laporan yang ditugaskan kepada Anda.</p>
        </div>
        <button wire:click="exportExcel"
            class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-emerald-600 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 hover:bg-emerald-100 sm:w-auto">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
            Export Excel
        </button>
    </div>

    {{-- Filter --}}
    <div class="rounded-xl border border-slate-200 bg-white p-4">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-4">
            <div class="md:col-span-1">
                <label class="mb-1 block text-xs font-medium text-slate-500">Cari</label>
                <input type="text" wire:model.live.debounce.400ms="search" placeholder="Judul / alamat / tiket…"
                    class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Status Tugas</label>
                <select wire:model.live="filterStatus"
                    class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">Semua Status</option>
                    <option value="ditugaskan">Ditugaskan</option>
                    <option value="dikerjakan">Dikerjakan</option>
                    <option value="selesai">Selesai</option>
                    <option value="dibatalkan">Dibatalkan</option>
                </select>
            </div>
            {{-- Tanggal: berdampingan sejak HP --}}
            <div class="grid grid-cols-2 gap-3 sm:col-span-2 md:contents">
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-500">Dari Tanggal</label>
                    <input type="date" wire:model.live="dariTanggal"
                        class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-500">Sampai Tanggal</label>
                    <input type="date" wire:model.live="sampaiTanggal"
                        class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                </div>
            </div>
        </div>
        <div class="mt-3 flex items-center justify-between">
            <p class="text-xs text-slate-400">Menampilkan {{ $tugas->total() }} tugas.</p>
            <button wire:click="resetFilter" class="text-xs font-semibold text-slate-500 hover:text-slate-700">Reset Filter</button>
        </div>
    </div>

    {{-- Tabel --}}
    @php
        $badgeClass = fn ($s) => [
            'ditugaskan' => 'bg-amber-100 text-amber-700',
            'dikerjakan' => 'bg-blue-100 text-blue-700',
            'selesai'    => 'bg-emerald-100 text-emerald-700',
            'dibatalkan' => 'bg-slate-100 text-slate-500',
        ][$s] ?? 'bg-slate-100 text-slate-600';
    @endphp

    {{-- Tabel (desktop) --}}
    <div class="hidden overflow-hidden rounded-xl border border-slate-200 bg-white md:block">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-3 font-medium">Laporan</th>
                    <th class="px-4 py-3 font-medium">Kategori</th>
                    <th class="px-4 py-3 font-medium">Status Tugas</th>
                    <th class="px-4 py-3 font-medium">Ditugaskan</th>
                    <th class="px-4 py-3 text-right font-medium">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($tugas as $a)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <div class="font-semibold text-slate-800">{{ $a->report?->judul ?? 'Laporan' }}</div>
                            <div class="text-xs text-slate-400">{{ $a->report?->tiket_no }} · {{ $a->report?->alamat ?? '-' }}</div>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $a->report?->kategori?->nama ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-md px-2 py-1 text-xs font-semibold capitalize {{ $badgeClass($a->status) }}">{{ $a->status }}</span>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ optional($a->assigned_at)->format('d M Y H:i') ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end">
                                <a href="{{ route('petugas.tugas.detail', $a->report_id) }}"
                                   class="rounded-lg bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-800">Detail</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-slate-400">Tidak ada tugas yang cocok dengan filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Kartu (mobile) --}}
    <div class="space-y-3 md:hidden">
        @forelse ($tugas as $a)
            <a href="{{ route('petugas.tugas.detail', $a->report_id) }}"
               class="block rounded-xl border border-slate-200 bg-white p-4 active:bg-slate-50">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-slate-800">{{ $a->report?->judul ?? 'Laporan' }}</p>
                        <p class="mt-0.5 text-xs text-slate-400">{{ $a->report?->tiket_no }}</p>
                    </div>
                    <span class="shrink-0 rounded-md px-2 py-1 text-xs font-semibold capitalize {{ $badgeClass($a->status) }}">{{ $a->status }}</span>
                </div>
                <div class="mt-3 space-y-1.5 text-sm">
                    <div class="flex items-center gap-2 text-slate-600">
                        <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5a1.99 1.99 0 011.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.99 1.99 0 013 12V7a4 4 0 014-4z"/></svg>
                        <span class="truncate">{{ $a->report?->kategori?->nama ?? '—' }}</span>
                    </div>
                    <div class="flex items-center gap-2 text-slate-600">
                        <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span class="truncate">{{ $a->report?->alamat ?? '-' }}</span>
                    </div>
                    <div class="flex items-center gap-2 text-slate-500">
                        <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span>{{ optional($a->assigned_at)->format('d M Y H:i') ?? '-' }}</span>
                    </div>
                </div>
                <div class="mt-3 flex items-center justify-end text-sm font-semibold text-emerald-700">
                    Lihat Detail
                    <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </div>
            </a>
        @empty
            <div class="rounded-xl border border-slate-200 bg-white px-4 py-10 text-center text-sm text-slate-400">
                Tidak ada tugas yang cocok dengan filter.
            </div>
        @endforelse
    </div>

    <div>{{ $tugas->links() }}</div>
</div>