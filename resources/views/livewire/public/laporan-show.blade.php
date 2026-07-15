<div class="mx-auto max-w-3xl px-4 py-10">
    <a href="{{ route('publik.laporan.index') }}" class="text-sm font-medium text-primary-500 hover:text-primary-700">← Semua Laporan</a>

    <div class="mt-4">
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs font-medium text-primary-500">{{ $report->kategori?->nama }}</span>
            <x-status-badge :status="$report->status" />
            <span class="text-xs text-gray-400">#{{ $report->tiket_no }}</span>
        </div>
        <h1 class="mt-2 text-2xl font-bold text-primary-900">{{ $report->judul }}</h1>
        <p class="mt-1 text-sm text-gray-400">{{ $report->banjarDinas?->nama }} · {{ $report->alamat }} · {{ $report->created_at->translatedFormat('d F Y') }}</p>
    </div>

    @if ($report->foto)
        <img src="{{ asset('storage/' . $report->foto) }}" class="mt-6 aspect-video w-full rounded-2xl border border-gray-200 object-cover" alt="">
    @endif

    @if ($report->images->isNotEmpty())
        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
            @foreach ($report->images as $img)
                <a href="{{ asset('storage/' . $img->path) }}" target="_blank"><img src="{{ asset('storage/' . $img->path) }}" class="aspect-square w-full rounded-xl border border-gray-200 object-cover" alt=""></a>
            @endforeach
        </div>
    @endif

    @if ($report->deskripsi)
        <p class="mt-6 text-sm leading-relaxed text-gray-700">{{ $report->deskripsi }}</p>
    @endif

    @if ($report->lat && $report->lng)
        <div wire:ignore class="mt-6" x-data x-init="
            const map = L.map($refs.map).setView([{{ $report->lat }}, {{ $report->lng }}], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'&copy; OpenStreetMap'}).addTo(map);
            const pin = (c) => L.divIcon({ className:'', iconSize:[26,36], iconAnchor:[13,36], popupAnchor:[0,-32], html:`<svg width='26' height='36' viewBox='0 0 26 36' xmlns='http://www.w3.org/2000/svg'><path d='M13 0C5.8 0 0 5.8 0 13c0 9.2 13 23 13 23s13-13.8 13-23C26 5.8 20.2 0 13 0z' fill='${c}'/><circle cx='13' cy='13' r='5' fill='white'/></svg>` });
            L.marker([{{ $report->lat }}, {{ $report->lng }}], { icon: pin('#ef4444') }).addTo(map);
            setTimeout(()=>map.invalidateSize(),200);
        "><div x-ref="map" class="h-64 w-full rounded-2xl border border-gray-200"></div></div>
    @endif

    <h2 class="mt-10 text-lg font-semibold text-primary-900">Progres Penanganan</h2>
    @if ($progress->isEmpty())
        <p class="mt-3 text-sm text-gray-400">Belum ada pembaruan progres.</p>
    @else
        <ol class="mt-4 space-y-4 border-l-2 border-primary-100 pl-5">
            @foreach ($progress as $pr)
                <li class="relative">
                    <span class="absolute -left-[27px] mt-1 h-3 w-3 rounded-full bg-primary-500 ring-4 ring-primary-50"></span>
                    <div class="flex items-center gap-2">
                        <x-status-badge :status="$pr->status_progress" />
                        <span class="text-xs text-gray-400">{{ $pr->created_at->translatedFormat('d M Y H:i') }}</span>
                    </div>
                    @if ($pr->catatan)<p class="mt-1 text-sm text-gray-700">{{ $pr->catatan }}</p>@endif
                    @if ($pr->foto_bukti)<img src="{{ asset('storage/' . $pr->foto_bukti) }}" class="mt-2 h-32 rounded-lg border border-gray-200 object-cover" alt="">@endif
                </li>
            @endforeach
        </ol>
    @endif
</div>