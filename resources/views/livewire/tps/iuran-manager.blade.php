<div class="space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-primary-900">Iuran Langganan</h1>
            <p class="mt-1 text-sm text-gray-500">
                @if ($tps?->is_berbayar)
                    Tarif: Rp {{ number_format($tps->tarif, 0, ',', '.') }} / bulan
                @else
                    TPS ini tidak memungut iuran.
                @endif
            </p>
        </div>
        @if ($tps?->is_berbayar)
            <button wire:click="buatTagihanBulanIni" class="flex-none rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700">
                Buat Tagihan {{ now()->format('M Y') }}
            </button>
        @endif
    </div>

    @if (session('ok'))
        <div class="rounded-xl border border-primary-100 bg-primary-50 px-4 py-3 text-sm font-medium text-primary-700">{{ session('ok') }}</div>
    @endif
    @if (session('err'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{{ session('err') }}</div>
    @endif

    <div class="flex flex-wrap gap-3">
        <select wire:model.live="periodeFilter" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
            <option value="">Semua periode</option>
            @foreach ($periodeList as $p)
                <option value="{{ $p }}">{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $p)->format('M Y') }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            @foreach (['semua' => 'Semua', 'menunggu' => 'Menunggu', 'lunas' => 'Lunas'] as $key => $label)
                <button wire:click="$set('statusFilter', '{{ $key }}')"
                        class="rounded-lg px-3 py-1.5 text-sm font-medium {{ $statusFilter === $key ? 'bg-primary-500 text-white' : 'border border-gray-200 bg-white text-gray-600 hover:bg-gray-50' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-6 py-3 font-semibold">Nasabah</th>
                    <th class="px-6 py-3 font-semibold">Periode</th>
                    <th class="px-6 py-3 font-semibold">Jumlah</th>
                    <th class="px-6 py-3 font-semibold">Status</th>
                    <th class="px-6 py-3 text-right font-semibold">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($iuran as $s)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3 text-primary-900">{{ $s->member?->user?->name ?? '—' }}</td>
                        <td class="px-6 py-3 text-gray-600">{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $s->periode)->format('M Y') }}</td>
                        <td class="px-6 py-3 text-gray-600">Rp {{ number_format($s->jumlah, 0, ',', '.') }}</td>
                        <td class="px-6 py-3">
                            @if ($s->status === 'lunas')
                                <span class="rounded-full bg-primary-50 px-2.5 py-0.5 text-xs font-medium text-primary-700">Lunas{{ $s->metode_bayar ? ' · ' . $s->metode_bayar : ' · tunai' }}</span>
                            @elseif ($s->status === 'gagal')
                                <span class="rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-600">Gagal</span>
                            @else
                                <span class="rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-700">Menunggu</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-right whitespace-nowrap">
                            @if ($s->status === 'menunggu')
                                <button wire:click="konfirmBayarSaldo({{ $s->id }})" class="text-sm font-medium text-primary-500 hover:text-primary-700">Bayar Saldo</button>
                                <button wire:click="tandaiLunas({{ $s->id }})" class="ml-3 text-sm font-medium text-gray-500 hover:text-gray-700">Tunai</button>
                                <button wire:click="konfirmHapus({{ $s->id }})" class="ml-3 text-sm font-medium text-red-600 hover:text-red-700">Hapus</button>
                            @else
                                <span class="text-xs text-gray-400">{{ $s->paid_at?->format('d M Y') }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-8 text-center text-gray-400">Belum ada tagihan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $iuran->links() }}</div>

    <x-confirm active="showPay" action="bayarSaldo" title="Bayar dari saldo?"
               message="Saldo nasabah akan dipotong sesuai tarif iuran." confirmLabel="Bayar" />

    <x-confirm active="showDelete" action="hapus" title="Hapus tagihan?"
               message="Tagihan yang belum dibayar akan dihapus." />
</div>