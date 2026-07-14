<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-primary-900">{{ $bs?->nama ?? 'Bank Sampah' }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ $bs?->alamat ?? 'Ringkasan aktivitas bank sampah Anda.' }}</p>
    </div>

    {{-- Statistik: 2 kolom HP, 4 desktop --}}
    <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
            <p class="text-xs text-gray-400">Setoran Hari Ini</p>
            <p class="mt-1 text-xl font-semibold text-primary-900 sm:text-2xl">{{ $todayCount }}</p>
            <p class="text-xs text-gray-500">Rp {{ number_format($todayNilai, 0, ',', '.') }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
            <p class="text-xs text-gray-400">Nilai Bulan Ini</p>
            <p class="mt-1 text-lg font-semibold text-primary-700 sm:text-2xl">Rp {{ number_format($monthNilai, 0, ',', '.') }}</p>
            <p class="text-xs text-gray-500">{{ number_format($monthBerat, 1, ',', '.') }} kg</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
            <p class="text-xs text-gray-400">Nasabah Dilayani</p>
            <p class="mt-1 text-xl font-semibold text-primary-900 sm:text-2xl">{{ $nasabahCount }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
            <p class="text-xs text-gray-400">Jumlah Petugas</p>
            <p class="mt-1 text-xl font-semibold text-primary-900 sm:text-2xl">{{ $petugasCount }}</p>
        </div>
    </div>

    {{-- Tren --}}
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
        <p class="text-sm font-semibold text-primary-900">Tren Setoran 7 Hari (Rp)</p>
        <div wire:ignore class="mt-3 h-52 sm:h-56" x-data x-init="
            new Chart($refs.bar, {
                type: 'bar',
                data: { labels: @js($trenLabels), datasets: [{ label: 'Setoran (Rp)', data: @js($trenData), backgroundColor: '#057D5D', borderRadius: 6 }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
            });
        ">
            <canvas x-ref="bar"></canvas>
        </div>
    </div>

    {{-- Setoran terbaru --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-4 py-4 sm:px-6">
            <p class="text-sm font-semibold text-primary-900">Setoran Terbaru</p>
        </div>

        {{-- Tabel (desktop) --}}
        <table class="hidden w-full text-left text-sm sm:table">
            <tbody class="divide-y divide-gray-100">
                @forelse ($recent as $d)
                    <tr>
                        <td class="px-6 py-3 text-primary-900">{{ $d->nasabah?->name ?? '—' }}</td>
                        <td class="px-6 py-3 text-gray-500">oleh {{ $d->petugas?->name ?? '—' }}</td>
                        <td class="px-6 py-3 text-gray-500">{{ number_format($d->total_berat, 2, ',', '.') }} kg</td>
                        <td class="px-6 py-3 text-right font-medium text-primary-700">Rp {{ number_format($d->total_nilai, 0, ',', '.') }}</td>
                        <td class="px-6 py-3 text-right text-xs text-gray-400">{{ $d->created_at->format('d M H:i') }}</td>
                    </tr>
                @empty
                    <tr><td class="px-6 py-8 text-center text-gray-400">Belum ada setoran.</td></tr>
                @endforelse
            </tbody>
        </table>

        {{-- Kartu (mobile) --}}
        <div class="divide-y divide-gray-100 sm:hidden">
            @forelse ($recent as $d)
                <div class="px-4 py-3">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="truncate font-medium text-primary-900">{{ $d->nasabah?->name ?? '—' }}</p>
                            <p class="truncate text-xs text-gray-400">oleh {{ $d->petugas?->name ?? '—' }}</p>
                        </div>
                        <p class="shrink-0 font-medium text-primary-700">Rp {{ number_format($d->total_nilai, 0, ',', '.') }}</p>
                    </div>
                    <div class="mt-1 flex items-center justify-between text-xs text-gray-400">
                        <span>{{ number_format($d->total_berat, 2, ',', '.') }} kg</span>
                        <span>{{ $d->created_at->format('d M H:i') }}</span>
                    </div>
                </div>
            @empty
                <div class="px-4 py-8 text-center text-gray-400">Belum ada setoran.</div>
            @endforelse
        </div>
    </div>
</div>