<div class="space-y-6">
    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Dashboard Petugas</h1>
        <p class="text-slate-500 text-sm">Ringkasan tugas penanganan laporan yang ditugaskan kepada Anda.</p>
    </div>

    {{-- Kartu ringkasan --}}
    <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
        @php
            $cards = [
                ['label' => 'Tugas Aktif', 'value' => $aktif, 'sub' => 'Ditugaskan / dikerjakan', 'ring' => 'bg-amber-50 text-amber-700'],
                ['label' => 'Selesai Bulan Ini', 'value' => $selesaiBulan, 'sub' => now()->translatedFormat('F Y'), 'ring' => 'bg-emerald-50 text-emerald-700'],
                ['label' => 'Total Selesai', 'value' => $selesaiTotal, 'sub' => 'Sepanjang waktu', 'ring' => 'bg-blue-50 text-blue-700'],
                ['label' => 'Total Ditangani', 'value' => $totalDitangani, 'sub' => 'Semua penugasan', 'ring' => 'bg-slate-100 text-slate-700'],
            ];
        @endphp
        @foreach ($cards as $c)
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-sm text-slate-500">{{ $c['label'] }}</p>
                <p class="mt-1 text-xl font-bold text-slate-800 sm:text-2xl">{{ number_format($c['value']) }}</p>
                <span class="mt-2 inline-block rounded-md px-2 py-0.5 text-xs font-semibold {{ $c['ring'] }}">{{ $c['sub'] }}</span>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-4 sm:gap-6 lg:grid-cols-5">
        {{-- Tren 7 hari --}}
        <div class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5 lg:col-span-2">
            <h2 class="mb-1 text-base font-bold text-slate-800">Aktivitas 7 Hari Terakhir</h2>
            <p class="mb-3 text-xs text-slate-400">Jumlah update progress per hari.</p>
            <div wire:ignore class="h-48 sm:h-52"
                 x-data="petugasTren(@js($trenLabels), @js($trenData))"
                 x-init="init()">
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>

        {{-- Tugas terbaru --}}
        <div class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5 lg:col-span-3">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-base font-bold text-slate-800">Tugas Terbaru</h2>
                <a href="{{ route('petugas.tugas') }}" class="text-sm font-semibold text-emerald-700 hover:text-emerald-800">Lihat semua</a>
            </div>
            @forelse ($recent as $a)
                <a href="{{ route('petugas.tugas.detail', $a->report_id) }}"
                   class="flex items-center gap-3 border-t border-slate-100 py-3 first:border-t-0 hover:bg-slate-50">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z"/></svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-slate-800">{{ $a->report?->judul ?? 'Laporan' }}</p>
                        <p class="truncate text-xs text-slate-400">{{ $a->report?->kategori?->nama ?? '—' }} · {{ $a->report?->alamat ?? '-' }}</p>
                    </div>
                    @php
                        $badge = [
                            'ditugaskan' => 'bg-amber-100 text-amber-700',
                            'dikerjakan' => 'bg-blue-100 text-blue-700',
                            'selesai'    => 'bg-emerald-100 text-emerald-700',
                            'dibatalkan' => 'bg-slate-100 text-slate-500',
                        ][$a->status] ?? 'bg-slate-100 text-slate-600';
                    @endphp
                    <span class="shrink-0 rounded-md px-2 py-1 text-xs font-semibold capitalize {{ $badge }}">{{ $a->status }}</span>
                </a>
            @empty
                <p class="py-8 text-center text-sm text-slate-400">Belum ada tugas untuk Anda.</p>
            @endforelse
        </div>
    </div>

    @push('scripts')
        <script>
            function petugasTren(labels, data) {
                return {
                    chart: null,
                    init() {
                        this.chart = new Chart(this.$refs.canvas, {
                            type: 'bar',
                            data: {
                                labels: labels,
                                datasets: [{
                                    label: 'Update progress',
                                    data: data,
                                    backgroundColor: '#059669',
                                    borderRadius: 6,
                                }],
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { legend: { display: false } },
                                scales: {
                                    y: { beginAtZero: true, ticks: { precision: 0 } },
                                    x: { grid: { display: false } },
                                },
                            },
                        });
                    },
                };
            }
        </script>
    @endpush
</div>