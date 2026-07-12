<div class="mx-auto max-w-6xl space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-primary-900">Penarikan Saldo</h1>
        <p class="mt-1 text-sm text-gray-500">Setujui atau tolak permintaan penarikan saldo nasabah.</p>
    </div>

    @if (session('ok'))
        <div class="rounded-xl border border-primary-100 bg-primary-50 px-4 py-3 text-sm font-medium text-primary-700">{{ session('ok') }}</div>
    @endif
    @if (session('err'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{{ session('err') }}</div>
    @endif

    {{-- Filter status --}}
    <div class="flex flex-wrap gap-2">
        @foreach (['menunggu' => 'Menunggu', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak', 'selesai' => 'Selesai', 'semua' => 'Semua'] as $key => $label)
            <button wire:click="$set('statusFilter', '{{ $key }}')"
                    class="rounded-lg px-3 py-1.5 text-sm font-medium {{ $statusFilter === $key ? 'bg-primary-500 text-white' : 'border border-gray-200 bg-white text-gray-600 hover:bg-gray-50' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-6 py-3 font-semibold">Nasabah</th>
                    <th class="px-6 py-3 font-semibold">Jumlah</th>
                    <th class="px-6 py-3 font-semibold">Tujuan</th>
                    <th class="px-6 py-3 font-semibold">Saldo Kini</th>
                    <th class="px-6 py-3 font-semibold">Status</th>
                    <th class="px-6 py-3 text-right font-semibold">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($daftar as $w)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3">
                            <p class="text-primary-900">{{ $w->user?->name ?? '—' }}</p>
                            <p class="text-xs text-gray-400">{{ $w->created_at->format('d M Y H:i') }}</p>
                        </td>
                        <td class="px-6 py-3 font-medium text-primary-900">Rp {{ number_format($w->jumlah, 0, ',', '.') }}</td>
                        <td class="px-6 py-3 text-gray-600">
                            <p class="capitalize">{{ str_replace('_', ' ', $w->metode) }}</p>
                            <p class="text-xs text-gray-400">{{ $w->no_rekening }}</p>
                        </td>
                        <td class="px-6 py-3 text-gray-600">Rp {{ number_format($w->user?->wallet?->saldo ?? 0, 0, ',', '.') }}</td>
                        <td class="px-6 py-3">
                            @php
                                $badge = [
                                    'menunggu'  => 'bg-amber-50 text-amber-700',
                                    'disetujui' => 'bg-blue-50 text-blue-700',
                                    'ditolak'   => 'bg-red-50 text-red-600',
                                    'selesai'   => 'bg-primary-50 text-primary-700',
                                ][$w->status] ?? 'bg-gray-100 text-gray-500';
                            @endphp
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badge }}">{{ $statusLabels[$w->status] ?? $w->status }}</span>
                            @if ($w->status === 'ditolak' && $w->catatan)
                                <p class="mt-1 text-xs text-gray-400">{{ $w->catatan }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-right whitespace-nowrap">
                            @if ($w->status === 'menunggu')
                                <button wire:click="konfirmSetujui({{ $w->id }})" class="text-sm font-medium text-primary-500 hover:text-primary-700">Setujui</button>
                                <button wire:click="konfirmTolak({{ $w->id }})" class="ml-3 text-sm font-medium text-red-600 hover:text-red-700">Tolak</button>
                            @elseif ($w->status === 'disetujui')
                                <button wire:click="konfirmSelesai({{ $w->id }})" class="text-sm font-medium text-primary-500 hover:text-primary-700">Tandai Selesai</button>
                            @else
                                <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-6 py-8 text-center text-gray-400">Tidak ada permintaan penarikan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $daftar->links() }}</div>

    <x-confirm active="showApprove" action="setujui" title="Setujui penarikan?"
               message="Saldo nasabah akan langsung dipotong sejumlah penarikan. Pastikan transfer dana dilakukan." confirmLabel="Setujui" />

    <x-confirm active="showFinish" action="selesaikan" title="Tandai selesai?"
               message="Tandai penarikan ini sebagai selesai setelah dana ditransfer." confirmLabel="Selesai" />

    {{-- Modal tolak dengan alasan --}}
    <x-modal active="showReject" max-width="max-w-md">
        <form wire:submit="tolak">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-base font-semibold text-primary-900">Tolak Penarikan</h2>
            </div>
            <div class="px-6 py-5">
                <label class="block text-sm font-medium text-primary-900">Alasan (opsional)</label>
                <textarea wire:model="rejectCatatan" rows="3"
                          class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500"></textarea>
            </div>
            <div class="flex justify-end gap-2 border-t border-gray-200 px-6 py-4">
                <button type="button" wire:click="$set('showReject', false)" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-primary-900 hover:bg-gray-50">Batal</button>
                <button type="submit" class="rounded-lg bg-red-600 px-5 py-2 text-sm font-semibold text-white hover:bg-red-700">Tolak</button>
            </div>
        </form>
    </x-modal>
</div>