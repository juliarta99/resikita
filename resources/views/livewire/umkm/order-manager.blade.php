<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-primary-900">Pesanan</h1>
        <p class="mt-1 text-sm text-gray-500">Proses pesanan masuk dari pembeli.</p>
    </div>
    @if (session('ok'))
        <div class="rounded-xl border border-primary-100 bg-primary-50 px-4 py-3 text-sm font-medium text-primary-700">{{ session('ok') }}</div>
    @endif

    {{-- Filter status: scroll horizontal di HP --}}
    <div class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:flex-wrap sm:px-0 sm:pb-0">
        @foreach (['semua' => 'Semua', 'dibayar' => 'Dibayar', 'dikemas' => 'Dikemas', 'dikirim' => 'Dikirim', 'selesai' => 'Selesai', 'menunggu_bayar' => 'Menunggu Bayar', 'dibatalkan' => 'Dibatalkan'] as $key => $label)
            <button wire:click="$set('statusFilter', '{{ $key }}')"
                    class="shrink-0 rounded-lg px-3 py-1.5 text-sm font-medium {{ $statusFilter === $key ? 'bg-primary-500 text-white' : 'border border-gray-200 bg-white text-gray-600 hover:bg-gray-50' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    @php
        $badgeClass = fn ($s) => [
            'menunggu_bayar' => 'bg-gray-100 text-gray-500',
            'dibayar'        => 'bg-blue-50 text-blue-700',
            'dikemas'        => 'bg-amber-50 text-amber-700',
            'dikirim'        => 'bg-indigo-50 text-indigo-700',
            'selesai'        => 'bg-primary-50 text-primary-700',
            'dibatalkan'     => 'bg-red-50 text-red-600',
        ][$s] ?? 'bg-gray-100 text-gray-500';
    @endphp

    {{-- Tabel (desktop) --}}
    <div class="hidden overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm lg:block">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-6 py-3 font-semibold">Pesanan</th>
                    <th class="px-6 py-3 font-semibold">Pembeli</th>
                    <th class="px-6 py-3 font-semibold">Total</th>
                    <th class="px-6 py-3 font-semibold">Status</th>
                    <th class="px-6 py-3 text-right font-semibold">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($orders as $o)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3">
                            <p class="text-primary-900">#{{ $o->id }}</p>
                            <p class="text-xs text-gray-400">{{ $o->created_at->format('d M Y H:i') }}</p>
                        </td>
                        <td class="px-6 py-3 text-gray-600">{{ $o->user?->name ?? '—' }}</td>
                        <td class="px-6 py-3 font-medium text-primary-900">Rp {{ number_format($o->total, 0, ',', '.') }}</td>
                        <td class="px-6 py-3"><span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badgeClass($o->status) }}">{{ $statusLabels[$o->status] ?? $o->status }}</span></td>
                        <td class="px-6 py-3 text-right whitespace-nowrap">
                            <button wire:click="lihat({{ $o->id }})" class="text-sm font-medium text-gray-500 hover:text-gray-700">Detail</button>
                            @if ($o->status === 'dibayar')
                                <button wire:click="kemas({{ $o->id }})" class="ml-3 text-sm font-medium text-primary-500 hover:text-primary-700">Kemas</button>
                            @elseif ($o->status === 'dikemas')
                                <button wire:click="bukaKirim({{ $o->id }})" class="ml-3 text-sm font-medium text-primary-500 hover:text-primary-700">Kirim</button>
                            @elseif ($o->status === 'dikirim')
                                <button wire:click="selesai({{ $o->id }})" class="ml-3 text-sm font-medium text-primary-500 hover:text-primary-700">Selesai</button>
                            @endif
                            @if (! in_array($o->status, ['selesai', 'dibatalkan']))
                                <button wire:click="konfirmBatal({{ $o->id }})" class="ml-3 text-sm font-medium text-red-600 hover:text-red-700">Batal</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-8 text-center text-gray-400">Belum ada pesanan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Kartu (mobile & tablet) --}}
    <div class="space-y-3 lg:hidden">
        @forelse ($orders as $o)
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="font-medium text-primary-900">#{{ $o->id }}</p>
                        <p class="text-xs text-gray-400">{{ $o->created_at->format('d M Y H:i') }}</p>
                    </div>
                    <span class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badgeClass($o->status) }}">{{ $statusLabels[$o->status] ?? $o->status }}</span>
                </div>

                <div class="mt-2 flex items-end justify-between gap-2">
                    <p class="min-w-0 truncate text-sm text-gray-500">{{ $o->user?->name ?? '—' }}</p>
                    <p class="shrink-0 font-semibold text-primary-900">Rp {{ number_format($o->total, 0, ',', '.') }}</p>
                </div>

                <div class="mt-3 flex flex-wrap gap-2 border-t border-gray-100 pt-3">
                    <button wire:click="lihat({{ $o->id }})" class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50">Detail</button>
                    @if ($o->status === 'dibayar')
                        <button wire:click="kemas({{ $o->id }})" class="rounded-lg border border-primary-200 bg-primary-50 px-3 py-1.5 text-xs font-medium text-primary-700 hover:bg-primary-100">Kemas</button>
                    @elseif ($o->status === 'dikemas')
                        <button wire:click="bukaKirim({{ $o->id }})" class="rounded-lg border border-primary-200 bg-primary-50 px-3 py-1.5 text-xs font-medium text-primary-700 hover:bg-primary-100">Kirim</button>
                    @elseif ($o->status === 'dikirim')
                        <button wire:click="selesai({{ $o->id }})" class="rounded-lg border border-primary-200 bg-primary-50 px-3 py-1.5 text-xs font-medium text-primary-700 hover:bg-primary-100">Selesai</button>
                    @endif
                    @if (! in_array($o->status, ['selesai', 'dibatalkan']))
                        <button wire:click="konfirmBatal({{ $o->id }})" class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-100">Batal</button>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-gray-200 bg-white px-4 py-8 text-center text-sm text-gray-400">Belum ada pesanan.</div>
        @endforelse
    </div>

    <div>{{ $orders->links() }}</div>

    {{-- Detail pesanan --}}
    <x-modal active="showDetail" max-width="max-w-lg">
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
            <h2 class="text-base font-semibold text-primary-900">Detail Pesanan @if ($detail) #{{ $detail->id }} @endif</h2>
            <button type="button" wire:click="$set('showDetail', false)" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        @if ($detail)
            <div class="max-h-[70vh] space-y-4 overflow-y-auto px-4 py-5 text-sm sm:px-6">
                <div>
                    <p class="text-xs text-gray-400">Pembeli</p>
                    <p class="text-primary-900">{{ $detail->user?->name }}</p>
                    <p class="text-xs text-gray-500">{{ $detail->alamat_kirim }}</p>
                </div>
                <div class="divide-y divide-gray-100 rounded-lg border border-gray-200">
                    @foreach ($detail->items as $it)
                        <div class="flex items-center justify-between gap-2 px-3 py-2">
                            <div class="min-w-0">
                                <p class="truncate text-primary-900">{{ $it->nama_snapshot }}</p>
                                <p class="text-xs text-gray-400">{{ $it->qty }} × Rp {{ number_format($it->harga_snapshot, 0, ',', '.') }}</p>
                            </div>
                            <p class="shrink-0 text-gray-600">Rp {{ number_format($it->subtotal, 0, ',', '.') }}</p>
                        </div>
                    @endforeach
                </div>
                <div class="space-y-1 text-right">
                    <p class="text-xs text-gray-500">Ongkir: Rp {{ number_format($detail->ongkir, 0, ',', '.') }}</p>
                    <p class="font-semibold text-primary-900">Total: Rp {{ number_format($detail->total, 0, ',', '.') }}</p>
                    <p class="text-xs text-gray-400">Metode: {{ $detail->metode_bayar }}</p>
                    @if ($detail->no_resi)
                        <p class="text-xs text-gray-400">{{ strtoupper($detail->kurir) }} · {{ $detail->no_resi }}</p>
                    @endif
                </div>
            </div>
        @endif
    </x-modal>

    {{-- Modal kirim --}}
    <x-modal active="showShip" max-width="max-w-md">
        <form wire:submit="kirim">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-base font-semibold text-primary-900">Kirim Pesanan</h2>
            </div>
            <div class="space-y-4 px-4 py-5 sm:px-6">
                <div>
                    <label class="block text-sm font-medium text-primary-900">Kurir</label>
                    <input wire:model="kurir" placeholder="JNE / SiCepat / AnterAja" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                    @error('kurir') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-primary-900">No. Resi</label>
                    <input wire:model="no_resi" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                    @error('no_resi') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t border-gray-200 px-4 py-4 sm:px-6">
                <button type="button" wire:click="$set('showShip', false)" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-primary-900 hover:bg-gray-50">Batal</button>
                <button type="submit" class="rounded-lg bg-primary-500 px-5 py-2 text-sm font-semibold text-white hover:bg-primary-700">Tandai Dikirim</button>
            </div>
        </form>
    </x-modal>

    <x-confirm active="showCancel" action="batalkan" title="Batalkan pesanan?"
               message="Pesanan akan dibatalkan. Jika sudah dibayar via saldo, saldo pembeli dikembalikan otomatis." confirmLabel="Batalkan" />
</div>