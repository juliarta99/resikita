<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-primary-900">Harga Sampah Terkini</h1>
        <p class="mt-1 text-sm text-gray-500">Daftar harga berlaku untuk seluruh bank sampah. Hanya dapat diubah oleh admin sistem.</p>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-6 py-3 font-semibold">Jenis Sampah</th>
                    <th class="px-6 py-3 font-semibold">Satuan</th>
                    <th class="px-6 py-3 font-semibold">Harga</th>
                    <th class="px-6 py-3 font-semibold">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($prices as $p)
                    <tr class="hover:bg-gray-50 {{ $p->is_active ? '' : 'opacity-50' }}">
                        <td class="px-6 py-3 text-primary-900">{{ $p->jenis_sampah }}</td>
                        <td class="px-6 py-3 text-gray-500">{{ $p->satuan }}</td>
                        <td class="px-6 py-3 font-medium text-primary-700">Rp {{ number_format($p->harga_per_kg, 0, ',', '.') }}</td>
                        <td class="px-6 py-3">
                            @if ($p->is_active)
                                <span class="rounded-full bg-primary-50 px-2.5 py-0.5 text-xs font-medium text-primary-700">Aktif</span>
                            @else
                                <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-500">Nonaktif</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-6 py-8 text-center text-gray-400">Belum ada data harga.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>