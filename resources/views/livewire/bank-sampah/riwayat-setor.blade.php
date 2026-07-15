<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-primary-900">Riwayat Setor</h1>
            <p class="mt-1 text-sm text-gray-500">
                {{ $isAdmin ? 'Seluruh riwayat setoran bank sampah ini.' : 'Riwayat setoran yang Anda proses.' }}
            </p>
        </div>
        @if ($isAdmin)
            <button wire:click="export" class="w-full flex-none rounded-lg border border-primary-500 px-4 py-2 text-sm font-semibold text-primary-500 hover:bg-primary-50 sm:w-auto">
                Export Excel
            </button>
        @endif
    </div>

    <input wire:model.live.debounce.300ms="search" placeholder="Cari nasabah / kode QR…"
           class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500 sm:max-w-xs">

    {{-- Tabel (desktop) --}}
    <div class="hidden overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm sm:block">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-6 py-3 font-semibold">Tanggal</th>
                    <th class="px-6 py-3 font-semibold">Nasabah</th>
                    @if ($isAdmin)<th class="px-6 py-3 font-semibold">Petugas</th>@endif
                    <th class="px-6 py-3 font-semibold">Berat</th>
                    <th class="px-6 py-3 text-right font-semibold">Nilai</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($daftar as $d)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3 text-gray-500">{{ $d->created_at->format('d M Y H:i') }}</td>
                        <td class="px-6 py-3 text-primary-900">{{ $d->nasabah?->name ?? '—' }}<span class="block text-xs text-gray-400">{{ $d->nasabah?->kode_qr }}</span></td>
                        @if ($isAdmin)<td class="px-6 py-3 text-gray-600">{{ $d->petugas?->name ?? '—' }}</td>@endif
                        <td class="px-6 py-3 text-gray-600">{{ number_format($d->total_berat, 2, ',', '.') }} kg</td>
                        <td class="px-6 py-3 text-right font-medium text-primary-700">Rp {{ number_format($d->total_nilai, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $isAdmin ? 5 : 4 }}" class="px-6 py-8 text-center text-gray-400">Belum ada riwayat.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Kartu (mobile) --}}
    <div class="space-y-3 sm:hidden">
        @forelse ($daftar as $d)
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="truncate font-medium text-primary-900">{{ $d->nasabah?->name ?? '—' }}</p>
                        @if ($d->nasabah?->kode_qr)<p class="truncate text-xs text-gray-400">{{ $d->nasabah?->kode_qr }}</p>@endif
                    </div>
                    <p class="shrink-0 font-medium text-primary-700">Rp {{ number_format($d->total_nilai, 0, ',', '.') }}</p>
                </div>
                <div class="mt-2 flex items-center justify-between text-xs text-gray-400">
                    <span>{{ $d->created_at->format('d M Y H:i') }}</span>
                    <span>{{ number_format($d->total_berat, 2, ',', '.') }} kg</span>
                </div>
                @if ($isAdmin)
                    <p class="mt-1 text-xs text-gray-400">Petugas: {{ $d->petugas?->name ?? '—' }}</p>
                @endif
            </div>
        @empty
            <div class="rounded-xl border border-gray-200 bg-white px-4 py-8 text-center text-gray-400">Belum ada riwayat.</div>
        @endforelse
    </div>

    <div>{{ $daftar->links() }}</div>
</div>