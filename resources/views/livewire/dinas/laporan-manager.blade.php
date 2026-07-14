<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-primary-900">Pelaporan Sampah</h1>
        <p class="mt-1 text-sm text-gray-500">Verifikasi laporan warga, tugaskan petugas, dan pantau penanganan.</p>
    </div>
    @if (session('ok'))
        <div class="rounded-xl border border-primary-100 bg-primary-50 px-4 py-3 text-sm font-medium text-primary-700">{{ session('ok') }}</div>
    @endif

    {{-- Filter --}}
    <div class="space-y-3">
        {{-- Tombol status: scroll horizontal di HP --}}
        <div class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:flex-wrap sm:px-0 sm:pb-0">
            @foreach (['menunggu' => 'Menunggu', 'diverifikasi' => 'Diverifikasi', 'ditugaskan' => 'Ditugaskan', 'proses' => 'Proses', 'selesai' => 'Selesai', 'ditolak' => 'Ditolak', 'semua' => 'Semua'] as $key => $label)
                <button wire:click="$set('statusFilter', '{{ $key }}')"
                        class="shrink-0 rounded-lg px-3 py-1.5 text-sm font-medium {{ $statusFilter === $key ? 'bg-primary-500 text-white' : 'border border-gray-200 bg-white text-gray-600 hover:bg-gray-50' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
        {{-- Search + kategori: full width di HP, sejajar di desktop --}}
        <div class="flex flex-col gap-2 sm:flex-row">
            <input wire:model.live.debounce.300ms="search" placeholder="Cari judul / tiket…"
                   class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500 sm:w-56">
            <select wire:model.live="kategoriFilter"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500 sm:w-auto">
                <option value="">Semua kategori</option>
                @foreach ($kategoriList as $k)
                    <option value="{{ $k->id }}">{{ $k->nama }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @php
        $badgeClass = fn ($s) => [
            'menunggu'     => 'bg-amber-50 text-amber-700',
            'diverifikasi' => 'bg-blue-50 text-blue-700',
            'ditugaskan'   => 'bg-indigo-50 text-indigo-700',
            'proses'       => 'bg-purple-50 text-purple-700',
            'selesai'      => 'bg-primary-50 text-primary-700',
            'ditolak'      => 'bg-red-50 text-red-600',
        ][$s] ?? 'bg-gray-100 text-gray-500';
    @endphp

    {{-- Tabel (desktop) --}}
    <div class="hidden overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm lg:block">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-6 py-3 font-semibold">Tiket / Judul</th>
                    <th class="px-6 py-3 font-semibold">Kategori</th>
                    <th class="px-6 py-3 font-semibold">Lokasi</th>
                    <th class="px-6 py-3 font-semibold">Status</th>
                    <th class="px-6 py-3 text-right font-semibold">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($laporan as $r)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3">
                            <p class="text-xs text-gray-400">{{ $r->tiket_no }}</p>
                            <p class="text-primary-900">{{ $r->judul }}</p>
                        </td>
                        <td class="px-6 py-3 text-gray-600">{{ $r->kategori?->nama ?? '—' }}</td>
                        <td class="px-6 py-3 text-gray-600">{{ $r->banjarDinas?->nama ?? $r->alamat ?? '—' }}</td>
                        <td class="px-6 py-3"><span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badgeClass($r->status) }}">{{ $statusLabels[$r->status] ?? $r->status }}</span></td>
                        <td class="px-6 py-3 text-right whitespace-nowrap">
                            <button wire:click="lihat({{ $r->id }})" class="text-sm font-medium text-gray-500 hover:text-gray-700">Detail</button>
                            @if ($r->status === 'menunggu')
                                <button wire:click="verifikasi({{ $r->id }})" class="ml-3 text-sm font-medium text-primary-500 hover:text-primary-700">Verifikasi</button>
                                <button wire:click="konfirmTolak({{ $r->id }})" class="ml-3 text-sm font-medium text-red-600 hover:text-red-700">Tolak</button>
                            @elseif ($r->status === 'diverifikasi')
                                <button wire:click="bukaTugas({{ $r->id }})" class="ml-3 text-sm font-medium text-primary-500 hover:text-primary-700">Tugaskan</button>
                                <button wire:click="konfirmTolak({{ $r->id }})" class="ml-3 text-sm font-medium text-red-600 hover:text-red-700">Tolak</button>
                            @elseif (in_array($r->status, ['ditugaskan', 'proses']))
                                <button wire:click="tandaiSelesai({{ $r->id }})" class="ml-3 text-sm font-medium text-primary-500 hover:text-primary-700">Selesai</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-8 text-center text-gray-400">Tidak ada laporan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Kartu (mobile & tablet) --}}
    <div class="space-y-3 lg:hidden">
        @forelse ($laporan as $r)
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-xs text-gray-400">{{ $r->tiket_no }}</p>
                        <p class="font-medium text-primary-900">{{ $r->judul }}</p>
                    </div>
                    <span class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badgeClass($r->status) }}">{{ $statusLabels[$r->status] ?? $r->status }}</span>
                </div>

                <div class="mt-2 space-y-1 text-xs text-gray-500">
                    <p><span class="text-gray-400">Kategori:</span> {{ $r->kategori?->nama ?? '—' }}</p>
                    <p><span class="text-gray-400">Lokasi:</span> {{ $r->banjarDinas?->nama ?? $r->alamat ?? '—' }}</p>
                </div>

                <div class="mt-3 flex flex-wrap gap-2 border-t border-gray-100 pt-3">
                    <button wire:click="lihat({{ $r->id }})" class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50">Detail</button>
                    @if ($r->status === 'menunggu')
                        <button wire:click="verifikasi({{ $r->id }})" class="rounded-lg border border-primary-200 bg-primary-50 px-3 py-1.5 text-xs font-medium text-primary-700 hover:bg-primary-100">Verifikasi</button>
                        <button wire:click="konfirmTolak({{ $r->id }})" class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-100">Tolak</button>
                    @elseif ($r->status === 'diverifikasi')
                        <button wire:click="bukaTugas({{ $r->id }})" class="rounded-lg border border-primary-200 bg-primary-50 px-3 py-1.5 text-xs font-medium text-primary-700 hover:bg-primary-100">Tugaskan</button>
                        <button wire:click="konfirmTolak({{ $r->id }})" class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-100">Tolak</button>
                    @elseif (in_array($r->status, ['ditugaskan', 'proses']))
                        <button wire:click="tandaiSelesai({{ $r->id }})" class="rounded-lg border border-primary-200 bg-primary-50 px-3 py-1.5 text-xs font-medium text-primary-700 hover:bg-primary-100">Selesai</button>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-gray-200 bg-white px-4 py-8 text-center text-sm text-gray-400">Tidak ada laporan.</div>
        @endforelse
    </div>

    <div>{{ $laporan->links() }}</div>

    {{-- Detail laporan --}}
    <x-modal active="showDetail" max-width="max-w-2xl">
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
            <h2 class="text-base font-semibold text-primary-900">Detail Laporan @if ($detail) · {{ $detail->tiket_no }} @endif</h2>
            <button type="button" wire:click="$set('showDetail', false)" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        @if ($detail)
            <div class="max-h-[70vh] space-y-4 overflow-y-auto px-6 py-5 text-sm">
                <div>
                    <p class="text-lg font-semibold text-primary-900">{{ $detail->judul }}</p>
                    <p class="mt-1 text-gray-600">{{ $detail->deskripsi }}</p>
                </div>
                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div><span class="text-gray-400">Pelapor:</span> <span class="text-primary-900">{{ $detail->pelapor?->name }}</span></div>
                    <div><span class="text-gray-400">Kategori:</span> <span class="text-primary-900">{{ $detail->kategori?->nama }}</span></div>
                    <div class="col-span-2"><span class="text-gray-400">Alamat:</span> <span class="text-primary-900">{{ $detail->alamat ?? '—' }} {{ $detail->banjarDinas ? '(' . $detail->banjarDinas->nama . ')' : '' }}</span></div>
                </div>
                @if ($detail->foto || $detail->images->isNotEmpty())
                    <div>
                        <p class="text-xs font-semibold text-gray-400">Bukti Laporan</p>
                        <div class="mt-2 grid grid-cols-3 gap-2">
                            @if ($detail->foto)<a href="{{ asset('storage/' . $detail->foto) }}" target="_blank"><img src="{{ asset('storage/' . $detail->foto) }}" class="aspect-square w-full rounded-lg border border-gray-200 object-cover" alt=""></a>@endif
                            @foreach ($detail->images as $img)<a href="{{ asset('storage/' . $img->path) }}" target="_blank"><img src="{{ asset('storage/' . $img->path) }}" class="aspect-square w-full rounded-lg border border-gray-200 object-cover" alt=""></a>@endforeach
                        </div>
                    </div>
                @endif
                {{-- Peta lokasi --}}
                <div wire:ignore x-data="{ map: null, marker: null }"
                     @detail-opened.window="
                        setTimeout(() => {
                            const la = $event.detail.lat, ln = $event.detail.lng;
                            if (!this.map) {
                                this.map = L.map($refs.rmap).setView([la, ln], 15);
                                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(this.map);
                                this.marker = L.marker([la, ln]).addTo(this.map);
                            } else {
                                this.map.invalidateSize();
                                this.map.setView([la, ln], 15);
                                this.marker.setLatLng([la, ln]);
                            }
                        }, 250);
                     ">
                    <div x-ref="rmap" class="h-48 w-full rounded-lg border border-gray-200"></div>
                </div>
                @if ($detail->assignments->isNotEmpty())
                    <div>
                        <p class="text-xs font-semibold text-gray-400">Penugasan ({{ $detail->assignments->count() }})</p>
                        <ul class="mt-1 space-y-1">
                            @foreach ($detail->assignments as $a)
                                <li class="text-primary-900">{{ $a->petugas?->name }} <span class="text-xs text-gray-400">· {{ $a->assigned_at?->format('d M Y H:i') }}</span></li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if ($detail->progress->isNotEmpty())
                    <div>
                        <p class="text-xs font-semibold text-gray-400">Progress Penanganan</p>
                        <div class="mt-2 space-y-2">
                            @foreach ($detail->progress as $pr)
                                <div class="rounded-lg border border-gray-200 px-3 py-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-medium text-primary-700">{{ ucfirst($pr->status_progress) }}</span>
                                        <span class="text-xs text-gray-400">{{ $pr->created_at->format('d M H:i') }}</span>
                                    </div>
                                    <p class="mt-1 text-gray-600">{{ $pr->catatan }}</p>
                                    <p class="text-xs text-gray-400">oleh {{ $pr->petugas?->name }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </x-modal>

    {{-- Modal tugaskan --}}
    <x-modal active="showAssign" max-width="max-w-md">
        <form wire:submit="tugaskan">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-base font-semibold text-primary-900">Tugaskan Petugas</h2>
            </div>
            <div class="px-6 py-5">
                <label class="block text-sm font-medium text-primary-900">Petugas Lapangan <span class="font-normal text-gray-400">(bisa lebih dari satu)</span></label>
                <div class="mt-2 max-h-64 space-y-1 overflow-y-auto rounded-lg border border-gray-200 p-2">
                    @forelse ($petugasList as $p)
                        <label class="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-gray-50">
                            <input type="checkbox" value="{{ $p->id }}" wire:model="petugasIds" class="rounded border-gray-300 text-primary-500 focus:ring-primary-500">
                            <span class="text-sm text-primary-900">{{ $p->name }}</span>
                        </label>
                    @empty
                        <p class="px-2 py-2 text-sm text-gray-400">Belum ada petugas lapangan.</p>
                    @endforelse
                </div>
                @error('petugasIds') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                @error('petugasIds.*') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="flex justify-end gap-2 border-t border-gray-200 px-6 py-4">
                <button type="button" wire:click="$set('showAssign', false)" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-primary-900 hover:bg-gray-50">Batal</button>
                <button type="submit" class="rounded-lg bg-primary-500 px-5 py-2 text-sm font-semibold text-white hover:bg-primary-700">Tugaskan</button>
            </div>
        </form>
    </x-modal>

    {{-- Modal tolak --}}
    <x-modal active="showReject" max-width="max-w-md">
        <form wire:submit="tolak">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-base font-semibold text-primary-900">Tolak Laporan</h2>
            </div>
            <div class="px-6 py-5">
                <label class="block text-sm font-medium text-primary-900">Alasan (opsional)</label>
                <textarea wire:model="rejectCatatan" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500"></textarea>
            </div>
            <div class="flex justify-end gap-2 border-t border-gray-200 px-6 py-4">
                <button type="button" wire:click="$set('showReject', false)" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-primary-900 hover:bg-gray-50">Batal</button>
                <button type="submit" class="rounded-lg bg-red-600 px-5 py-2 text-sm font-semibold text-white hover:bg-red-700">Tolak</button>
            </div>
        </form>
    </x-modal>
</div>