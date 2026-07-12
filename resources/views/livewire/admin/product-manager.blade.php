<div class="mx-auto max-w-6xl space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-primary-900">Seluruh Produk</h1>
            <p class="mt-1 text-sm text-gray-500">Tinjau produk seluruh UMKM dan nonaktifkan bila perlu.</p>
        </div>
        <button wire:click="export" class="flex-none rounded-lg border border-primary-500 px-4 py-2 text-sm font-semibold text-primary-600 hover:bg-primary-50">Export Excel</button>
    </div>

    @if (session('ok'))
        <div class="rounded-xl border border-gray-200 bg-primary-50 px-4 py-3 text-sm font-medium text-primary-700">{{ session('ok') }}</div>
    @endif

    <div class="flex flex-wrap items-center gap-3">
        <input wire:model.live.debounce.300ms="search" placeholder="Cari produk…" class="w-full max-w-xs rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
        <select wire:model.live="umkmFilter" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
            <option value="">Semua UMKM</option>
            @foreach ($umkms as $u)<option value="{{ $u->id }}">{{ $u->nama }}</option>@endforeach
        </select>
        <select wire:model.live="aktifFilter" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
            <option value="semua">Semua status</option>
            <option value="aktif">Aktif</option>
            <option value="nonaktif">Nonaktif</option>
        </select>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-6 py-3 font-semibold">Produk</th>
                    <th class="px-6 py-3 font-semibold">UMKM</th>
                    <th class="px-6 py-3 font-semibold">Harga</th>
                    <th class="px-6 py-3 font-semibold">Stok</th>
                    <th class="px-6 py-3 font-semibold">Status</th>
                    <th class="px-6 py-3 text-right font-semibold">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($daftar as $p)
                    <tr class="hover:bg-gray-50 {{ $p->is_active ? '' : 'opacity-60' }}">
                        <td class="px-6 py-3 text-primary-900">{{ $p->nama }}<span class="block text-xs text-gray-400">{{ $p->kategori?->nama }}</span></td>
                        <td class="px-6 py-3 text-gray-600">{{ $p->umkm?->nama }}</td>
                        <td class="px-6 py-3 text-primary-700">Rp {{ number_format($p->harga, 0, ',', '.') }}</td>
                        <td class="px-6 py-3 text-gray-600">{{ $p->stok }}</td>
                        <td class="px-6 py-3">
                            @if ($p->is_active)<span class="rounded-full bg-primary-50 px-2.5 py-0.5 text-xs font-medium text-primary-700">Aktif</span>@else<span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-500">Nonaktif</span>@endif
                        </td>
                        <td class="px-6 py-3 text-right whitespace-nowrap">
                            <button wire:click="lihat({{ $p->id }})" class="text-sm font-medium text-primary-500 hover:text-primary-700">Detail</button>
                            <button wire:click="toggleAktif({{ $p->id }})" class="ml-3 text-sm font-medium {{ $p->is_active ? 'text-amber-600 hover:text-amber-700' : 'text-primary-600 hover:text-primary-700' }}">{{ $p->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-6 py-8 text-center text-gray-400">Tidak ada produk.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $daftar->links() }}</div>

    <x-modal active="showDetail" max-width="max-w-xl">
        @if ($selected)
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                <h2 class="text-base font-semibold text-primary-900">Detail Produk</h2>
                <button type="button" wire:click="$set('showDetail', false)" class="text-gray-400 hover:text-gray-600">✕</button>
            </div>
            <div class="max-h-[72vh] space-y-4 overflow-y-auto px-6 py-5">
                @if ($selected->images->isNotEmpty())
                    <div class="grid grid-cols-3 gap-2">
                        @foreach ($selected->images as $img)<img src="{{ asset('storage/' . $img->path) }}" class="aspect-square w-full rounded-lg border border-gray-200 object-cover" alt="">@endforeach
                    </div>
                @endif
                <div>
                    <h3 class="text-lg font-semibold text-primary-900">{{ $selected->nama }}</h3>
                    <p class="text-xs text-gray-400">{{ $selected->umkm?->nama }} · {{ $selected->kategori?->nama }}</p>
                </div>
                <div class="grid grid-cols-3 gap-3 text-center">
                    <div class="rounded-lg border border-gray-200 p-3"><p class="text-xs text-gray-400">Harga</p><p class="mt-1 font-semibold text-primary-700">Rp {{ number_format($selected->harga, 0, ',', '.') }}</p></div>
                    <div class="rounded-lg border border-gray-200 p-3"><p class="text-xs text-gray-400">Stok</p><p class="mt-1 font-semibold text-primary-900">{{ $selected->stok }}</p></div>
                    <div class="rounded-lg border border-gray-200 p-3"><p class="text-xs text-gray-400">Berat</p><p class="mt-1 font-semibold text-primary-900">{{ $selected->berat }} g</p></div>
                </div>
                @if ($selected->deskripsi)<p class="text-sm leading-relaxed text-gray-700">{{ $selected->deskripsi }}</p>@endif
            </div>
            <div class="flex justify-between border-t border-gray-200 px-6 py-4">
                <button type="button" wire:click="toggleAktif({{ $selected->id }})" class="rounded-lg border px-4 py-2 text-sm font-medium {{ $selected->is_active ? 'border-amber-300 text-amber-700 hover:bg-amber-50' : 'border-primary-300 text-primary-700 hover:bg-primary-50' }}">{{ $selected->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                <button type="button" wire:click="$set('showDetail', false)" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-primary-900 hover:bg-gray-50">Tutup</button>
            </div>
        @endif
    </x-modal>
</div>