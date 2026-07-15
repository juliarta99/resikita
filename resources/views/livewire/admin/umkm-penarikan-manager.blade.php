<div class="mx-auto max-w-6xl space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-primary-900">Penarikan Saldo UMKM</h1>
        <p class="mt-1 text-sm text-gray-500">Setujui atau tolak permintaan penarikan saldo UMKM.</p>
    </div>

    @if (session('ok'))
        <div class="rounded-xl border border-primary-100 bg-primary-50 px-4 py-3 text-sm font-medium text-primary-700">{{ session('ok') }}</div>
    @endif
    @if (session('err'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{{ session('err') }}</div>
    @endif

    <div class="flex flex-wrap gap-2">
        @foreach (['menunggu' => 'Menunggu', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak', 'semua' => 'Semua'] as $key => $label)
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
                    <th class="px-6 py-3 font-semibold">UMKM</th>
                    <th class="px-6 py-3 font-semibold">Jumlah</th>
                    <th class="px-6 py-3 font-semibold">Rekening Tujuan</th>
                    <th class="px-6 py-3 font-semibold">Status</th>
                    <th class="px-6 py-3 text-right font-semibold">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($daftar as $w)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3">
                            <p class="text-primary-900">{{ $w->umkm?->nama ?? '—' }}</p>
                            <p class="text-xs text-gray-400">{{ $w->created_at->format('d M Y H:i') }}</p>
                        </td>
                        <td class="px-6 py-3 font-semibold text-gray-800">Rp {{ number_format($w->jumlah, 0, ',', '.') }}</td>
                        <td class="px-6 py-3">
                            <p class="text-gray-800">{{ $w->nama_bank }} — {{ $w->no_rekening }}</p>
                            <p class="text-xs text-gray-500">a.n {{ $w->atas_nama }}</p>
                            @if ($w->catatan)<p class="text-xs text-red-500">{{ $w->catatan }}</p>@endif
                        </td>
                        <td class="px-6 py-3">
                            @php $c = ['menunggu'=>'bg-amber-100 text-amber-700','disetujui'=>'bg-primary-100 text-primary-700','ditolak'=>'bg-red-100 text-red-700'][$w->status] ?? 'bg-gray-100 text-gray-600'; @endphp
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $c }} capitalize">{{ $w->status }}</span>
                        </td>
                        <td class="px-6 py-3 text-right">
                            @if ($w->status === 'menunggu')
                                <button wire:click="setujui({{ $w->id }})" wire:confirm="Setujui penarikan ini? Saldo UMKM akan dipotong."
                                        class="rounded-lg bg-primary-500 px-3 py-1.5 text-xs font-semibold text-white hover:bg-primary-700">Setujui</button>
                                <button wire:click="konfirmTolak({{ $w->id }})"
                                        class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50">Tolak</button>
                            @else
                                <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-8 text-center text-sm text-gray-400">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $daftar->links() }}</div>

    {{-- Modal tolak --}}
    @if ($showReject)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h3 class="mb-3 text-lg font-semibold text-primary-900">Tolak Penarikan</h3>
                <label class="mb-1 block text-sm font-medium text-gray-700">Alasan (opsional)</label>
                <textarea wire:model="rejectCatatan" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></textarea>
                <div class="mt-4 flex justify-end gap-2">
                    <button wire:click="$set('showReject', false)" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Batal</button>
                    <button wire:click="tolak" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">Tolak Penarikan</button>
                </div>
            </div>
        </div>
    @endif
</div>