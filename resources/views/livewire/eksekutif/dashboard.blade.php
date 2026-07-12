<div class="space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-primary-900">Dashboard {{ $scopeLabel }}</h1>
            <p class="mt-1 text-sm text-gray-500">Ringkasan pengelolaan sampah wilayah Anda.</p>
        </div>
        <button wire:click="exportStatistik" class="flex-none rounded-lg border border-primary-500 px-4 py-2 text-sm font-semibold text-primary-600 hover:bg-primary-50">Export Statistik</button>
    </div>

    {{-- Kartu statistik --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([['Warga Terdaftar', number_format($stats['warga'], 0, ',', '.')], ['Bank Sampah', $stats['bankSampah']], ['TPS', $stats['tps']], ['UMKM Aktif', $stats['umkm']]] as [$label, $val])
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs text-gray-400">{{ $label }}</p>
                <p class="mt-1 text-2xl font-semibold text-primary-900">{{ $val }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs text-gray-400">Setoran Bulan Ini</p>
            <p class="mt-1 text-2xl font-semibold text-primary-700">Rp {{ number_format($stats['setoranNilai'], 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-gray-500">{{ number_format($stats['setoranBerat'], 1, ',', '.') }} kg · {{ $stats['setoranJumlah'] }} transaksi</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm lg:col-span-2">
            <div class="flex items-center justify-between">
                <p class="text-xs text-gray-400">Laporan Sampah</p>
                <p class="text-xs text-gray-400">Total {{ $stats['lapTotal'] }}</p>
            </div>
            <div class="mt-3 grid grid-cols-4 gap-3 text-center">
                <div class="rounded-lg bg-amber-50 py-3"><p class="text-xl font-semibold text-amber-700">{{ $stats['lapMenunggu'] }}</p><p class="text-xs text-amber-700/70">Menunggu</p></div>
                <div class="rounded-lg bg-blue-50 py-3"><p class="text-xl font-semibold text-blue-700">{{ $stats['lapProses'] }}</p><p class="text-xs text-blue-700/70">Proses</p></div>
                <div class="rounded-lg bg-primary-50 py-3"><p class="text-xl font-semibold text-primary-700">{{ $stats['lapSelesai'] }}</p><p class="text-xs text-primary-700/70">Selesai</p></div>
                <div class="rounded-lg bg-red-50 py-3"><p class="text-xl font-semibold text-red-600">{{ $stats['lapDitolak'] }}</p><p class="text-xs text-red-600/70">Ditolak</p></div>
            </div>
        </div>
    </div>

    {{-- Chart --}}
    <div class="grid gap-4 lg:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm lg:col-span-2">
            <p class="text-sm font-semibold text-primary-900">Tren Setoran 6 Bulan (Rp)</p>
            <div wire:ignore class="mt-3 h-64" x-data x-init="
                new Chart($refs.bar, {
                    type: 'bar',
                    data: { labels: @js($trenLabels), datasets: [{ label: 'Setoran (Rp)', data: @js($trenData), backgroundColor: '#057D5D', borderRadius: 6 }] },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
                });
            ">
                <canvas x-ref="bar"></canvas>
            </div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-primary-900">Status Laporan</p>
            <div wire:ignore class="mt-3 h-64" x-data x-init="
                new Chart($refs.doughnut, {
                    type: 'doughnut',
                    data: { labels: ['Menunggu','Proses','Selesai','Ditolak'], datasets: [{ data: @js($lapData), backgroundColor: ['#f59e0b','#3b82f6','#057D5D','#ef4444'] }] },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
                });
            ">
                <canvas x-ref="doughnut"></canvas>
            </div>
        </div>
    </div>

    {{-- Rekomendasi AI --}}
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary-50 text-primary-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16 2.5 6.5L22 12l-6.5 2.5L13 21l-2.5-6.5L4 12l6.5-2.5L13 3Z"/>
                        </svg>
                    </span>
                    <h2 class="text-base font-semibold text-primary-900">Rekomendasi AI Prioritas</h2>
                </div>
                <p class="mt-1 text-xs text-gray-500">
                    @if ($rekomendasi)
                        Dibuat {{ $rekomendasi->updated_at->format('d M Y H:i') }} · berdasarkan statistik bulan ini
                    @else
                        Belum dibuat untuk hari ini.
                    @endif
                </p>
            </div>
            <div class="flex flex-none gap-2">
                @if ($rekomendasi)
                    <button wire:click="exportRekomendasi" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-primary-900 hover:bg-gray-50">Export</button>
                @endif
                <button wire:click="generateAi" wire:loading.attr="disabled" wire:target="generateAi"
                        class="rounded-lg border border-primary-500 px-4 py-2 text-sm font-semibold text-primary-600 hover:bg-primary-50 disabled:opacity-60">
                    <span wire:loading.remove wire:target="generateAi">{{ $rekomendasi ? 'Perbarui' : 'Buat Rekomendasi' }}</span>
                    <span wire:loading wire:target="generateAi">Menganalisis…</span>
                </button>
            </div>
        </div>

        @if ($aiError)
            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $aiErrorMsg }}</div>
        @endif

        @if ($rekomendasi)
            <div class="ai-rec mt-4">{!! \Illuminate\Support\Str::markdown($rekomendasi->konten, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div>
        @elseif (! $aiError)
            <div class="mt-4 rounded-lg border border-dashed border-gray-300 px-4 py-6 text-center text-sm text-gray-400">
                Klik "Buat Rekomendasi" untuk analisis prioritas berbasis data. Disimpan untuk hari ini & bisa diekspor.
            </div>
        @endif
    </div>
</div>