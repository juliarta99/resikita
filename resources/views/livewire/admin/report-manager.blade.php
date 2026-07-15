<div class="mx-auto max-w-6xl space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-primary-900">Seluruh Laporan</h1>
            <p class="mt-1 text-sm text-gray-500">Pantau, tinjau, dan kelola seluruh laporan warga.</p>
        </div>
        <button wire:click="export" class="flex-none rounded-lg border border-primary-500 px-4 py-2 text-sm font-semibold text-primary-500 hover:bg-primary-50">Export Excel</button>
    </div>

    @if (session('ok'))
        <div class="rounded-xl border border-gray-200 bg-primary-50 px-4 py-3 text-sm font-medium text-primary-700">{{ session('ok') }}</div>
    @endif

    <div class="flex flex-wrap items-center gap-3">
        <input wire:model.live.debounce.300ms="search" placeholder="Cari judul / tiket / alamat…" class="w-full max-w-xs rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
        <select wire:model.live="kategoriFilter" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
            <option value="">Semua kategori</option>
            @foreach ($kategoris as $k)<option value="{{ $k->id }}">{{ $k->nama }}</option>@endforeach
        </select>
        <select wire:model.live="statusFilter" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
            @foreach (['semua'=>'Semua status','menunggu'=>'Menunggu','diverifikasi'=>'Diverifikasi','ditugaskan'=>'Ditugaskan','proses'=>'Diproses','selesai'=>'Selesai','ditolak'=>'Ditolak'] as $k=>$v)
                <option value="{{ $k }}">{{ $v }}</option>
            @endforeach
        </select>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-6 py-3 font-semibold">Tiket</th>
                    <th class="px-6 py-3 font-semibold">Judul</th>
                    <th class="px-6 py-3 font-semibold">Kategori</th>
                    <th class="px-6 py-3 font-semibold">Status</th>
                    <th class="px-6 py-3 text-right font-semibold">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($daftar as $r)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3 text-gray-500">#{{ $r->tiket_no }}</td>
                        <td class="px-6 py-3 text-primary-900">{{ $r->judul }}<span class="block text-xs text-gray-400">{{ $r->banjarDinas?->nama }}</span></td>
                        <td class="px-6 py-3 text-gray-600">{{ $r->kategori?->nama }}</td>
                        <td class="px-6 py-3"><x-status-badge :status="$r->status" /></td>
                        <td class="px-6 py-3 text-right whitespace-nowrap">
                            <button wire:click="lihat({{ $r->id }})" class="text-sm font-medium text-primary-500 hover:text-primary-700">Detail</button>
                            <button wire:click="konfirmHapus({{ $r->id }})" class="ml-3 text-sm font-medium text-red-600 hover:text-red-700">Hapus</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-8 text-center text-gray-400">Tidak ada laporan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $daftar->links() }}</div>

    {{-- Detail --}}
    <x-modal active="showDetail" max-width="max-w-2xl">
        @if ($selected)
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                <h2 class="text-base font-semibold text-primary-900">Detail Laporan</h2>
                <button type="button" wire:click="$set('showDetail', false)" class="text-gray-400 hover:text-gray-600">✕</button>
            </div>
            <div class="max-h-[72vh] space-y-4 overflow-y-auto px-6 py-5">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-medium text-primary-500">{{ $selected->kategori?->nama }}</span>
                    <x-status-badge :status="$selected->status" />
                    <span class="text-xs text-gray-400">#{{ $selected->tiket_no }}</span>
                </div>
                <h3 class="text-lg font-semibold text-primary-900">{{ $selected->judul }}</h3>
                <p class="text-xs text-gray-400">Pelapor: {{ $selected->pelapor?->name ?? '—' }} · {{ $selected->created_at->format('d M Y H:i') }}</p>
                <p class="text-xs text-gray-400">{{ $selected->banjarDinas?->nama }} · {{ $selected->alamat }}</p>
                @if ($selected->foto || $selected->images->isNotEmpty())
                    <div>
                        <p class="text-xs font-semibold text-gray-400">Bukti Laporan</p>
                        <div class="mt-2 grid grid-cols-3 gap-2">
                            @if ($selected->foto)<a href="{{ asset('storage/' . $selected->foto) }}" target="_blank"><img src="{{ asset('storage/' . $selected->foto) }}" class="aspect-square w-full rounded-lg border border-gray-200 object-cover" alt=""></a>@endif
                            @foreach ($selected->images as $img)<a href="{{ asset('storage/' . $img->path) }}" target="_blank"><img src="{{ asset('storage/' . $img->path) }}" class="aspect-square w-full rounded-lg border border-gray-200 object-cover" alt=""></a>@endforeach
                        </div>
                    </div>
                @endif
                @if ($selected->deskripsi)<p class="text-sm leading-relaxed text-gray-700">{{ $selected->deskripsi }}</p>@endif
                @if ($selected->assignments->isNotEmpty())
                    <div>
                        <p class="text-xs font-semibold text-gray-400">Petugas Ditugaskan ({{ $selected->assignments->count() }})</p>
                        <p class="mt-1 text-sm text-primary-900">{{ $selected->assignments->map(fn ($a) => $a->petugas?->name)->filter()->implode(', ') }}</p>
                    </div>
                @endif

                <div wire:ignore x-data="{ map:null }" x-on:detail-opened.window="
                    (e) => { if (!e.detail.lat) { return; } $nextTick(() => { if (map) { map.remove(); map=null; } map = L.map($refs.dmap).setView([e.detail.lat, e.detail.lng], 15); L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'&copy; OSM'}).addTo(map); L.marker([e.detail.lat, e.detail.lng]).addTo(map); setTimeout(()=>map.invalidateSize(),200); }); }
                ">
                    @if ($selected->lat && $selected->lng)<div x-ref="dmap" class="h-56 w-full rounded-lg border border-gray-200"></div>@endif
                </div>

                @if ($selected->progress->isNotEmpty())
                    <div>
                        <p class="text-sm font-semibold text-primary-900">Progres</p>
                        <ol class="mt-2 space-y-2 border-l-2 border-primary-100 pl-4">
                            @foreach ($selected->progress->sortByDesc('created_at') as $pr)
                                <li><div class="flex items-center gap-2"><x-status-badge :status="$pr->status_progress" /><span class="text-xs text-gray-400">{{ $pr->created_at->format('d M H:i') }}</span></div>@if ($pr->catatan)<p class="mt-0.5 text-xs text-gray-600">{{ $pr->catatan }}</p>@endif</li>
                            @endforeach
                        </ol>
                    </div>
                @endif
            </div>
            <div class="flex justify-end border-t border-gray-200 px-6 py-4">
                <button type="button" wire:click="$set('showDetail', false)" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-primary-900 hover:bg-gray-50">Tutup</button>
            </div>
        @endif
    </x-modal>

    <x-confirm active="showDelete" action="hapus" title="Hapus laporan?" message="Laporan beserta progres & penugasannya akan dihapus permanen." />
</div>