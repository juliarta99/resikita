<div class="mx-auto max-w-3xl space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-primary-900">Rekomendasi AI</h1>
        <p class="mt-1 text-sm text-gray-500">Saran strategi berbasis data penjualan &amp; produk Anda.</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16 2.5 6.5L22 12l-6.5 2.5L13 21l-2.5-6.5L4 12l6.5-2.5L13 3Z"/></svg>
                    </span>
                    <h2 class="text-base font-semibold text-primary-900">Saran Prioritas</h2>
                </div>
                <p class="mt-1 text-xs text-gray-500">
                    @if ($rekomendasi) Dibuat {{ $rekomendasi->updated_at->format('d M Y H:i') }} · berlaku hari ini @else Belum dibuat untuk hari ini. @endif
                </p>
            </div>
            <div class="flex gap-2 sm:flex-none">
                @if ($rekomendasi)<button wire:click="exportRekomendasi" class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-primary-900 hover:bg-gray-50 sm:flex-none">Export</button>@endif
                <button wire:click="generateAi" wire:loading.attr="disabled" wire:target="generateAi" class="flex-1 rounded-lg border border-primary-500 px-4 py-2 text-sm font-semibold text-primary-600 hover:bg-primary-50 disabled:opacity-60 sm:flex-none">
                    <span wire:loading.remove wire:target="generateAi">{{ $rekomendasi ? 'Perbarui' : 'Buat Rekomendasi' }}</span>
                    <span wire:loading wire:target="generateAi">Menganalisis…</span>
                </button>
            </div>
        </div>
        @if ($aiError)<div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $aiErrorMsg }}</div>@endif
        @if ($rekomendasi)
            <div class="ai-rec mt-4">{!! \Illuminate\Support\Str::markdown($rekomendasi->konten, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div>
        @elseif (! $aiError)
            <div class="mt-4 rounded-lg border border-dashed border-gray-300 px-4 py-6 text-center text-sm text-gray-400">Klik "Buat Rekomendasi" untuk analisis berbasis penjualan &amp; produk. Disimpan untuk hari ini &amp; bisa diekspor.</div>
        @endif
    </div>
</div>