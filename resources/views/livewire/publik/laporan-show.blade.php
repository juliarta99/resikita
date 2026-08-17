@php use App\Enums\StatusLaporan; @endphp

<div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
    <a href="{{ route('publik.laporan') }}" wire:navigate
       class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-primary-900">
        <x-ui.ikon nama="keluar" class="h-4 w-4 rotate-180"/>
        Kembali ke daftar laporan
    </a>

    <header class="mt-6">
        <div class="flex flex-wrap items-center gap-2">
            <span class="font-mono text-sm text-gray-500">{{ $laporan->tiket }}</span>
            <x-ui.lencana :status="$laporan->status"/>
            @if ($laporan->deskripsi_sumber === \App\Enums\SumberInput::Suara)
                <x-ui.lencana warna="nila" label="Dilaporkan lewat suara"/>
            @endif
        </div>

        <h1 class="mt-3 text-3xl font-bold tracking-tight text-primary-900">{{ $laporan->judul }}</h1>

        <p class="mt-3 text-sm text-gray-500">
            Dilaporkan {{ $laporan->created_at->translatedFormat('j F Y') }}
            @if ($laporan->kategori) &middot; {{ $laporan->kategori->nama }} @endif
        </p>
    </header>

    {{-- Ringkasan penanganan --}}
    <div class="mt-8 rounded-2xl border border-gray-200 p-5">
        <dl class="grid gap-4 sm:grid-cols-3">
            <div>
                <dt class="text-xs text-gray-500">Status saat ini</dt>
                <dd class="mt-1 font-semibold text-primary-900">{{ $laporan->status->label() }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-500">Ditangani di tingkat</dt>
                <dd class="mt-1 font-semibold text-primary-900">
                    {{ $laporan->penanggung_jawab_type?->label() ?? 'Belum ditentukan' }}
                </dd>
            </div>
            <div>
                <dt class="text-xs text-gray-500">Lama penanganan</dt>
                <dd class="mt-1 font-semibold text-primary-900">
                    @if ($laporan->waktuResponsJam() !== null)
                        {{ number_format($laporan->waktuResponsJam(), 1, ',', '.') }} jam
                    @elseif ($laporan->status->isFinal())
                       ,
                    @else
                        {{ $laporan->created_at->diffForHumans(short: true, syntax: \Carbon\CarbonInterface::DIFF_ABSOLUTE) }} berjalan
                    @endif
                </dd>
            </div>
        </dl>

        @if ($laporan->alasan_routing === \App\Enums\AlasanRouting::WilayahBelumTerjangkau)
            <p class="mt-5 flex items-start gap-2.5 rounded-xl bg-amber-50 p-3 text-sm text-amber-900">
                <x-ui.ikon nama="info" class="h-4 w-4 flex-none text-amber-600"/>
                Pemerintah daerah wilayah ini belum bergabung dengan Resikita. Laporan diteruskan
                fasilitator ke dinas terkait di luar sistem, dan catatan komunikasinya ada di bawah.
            </p>
        @endif
    </div>

    {{-- Isi --}}
    <section class="mt-8" aria-labelledby="judul-isi">
        <h2 id="judul-isi" class="text-sm font-semibold text-primary-900">Keterangan pelapor</h2>
        <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-gray-700">{{ $laporan->deskripsi }}</p>

        <dl class="mt-5 grid gap-4 border-t border-gray-100 pt-5 sm:grid-cols-2 text-sm">
            <div>
                <dt class="text-xs text-gray-500">Wilayah</dt>
                <dd class="mt-0.5 text-primary-900">
                    {{ collect([
                        $laporan->desa?->nama, $laporan->kecamatan?->nama,
                        $laporan->kabupaten?->nama, $laporan->provinsi?->nama,
                    ])->filter()->implode(', ') ?: 'Belum teridentifikasi' }}
                </dd>
            </div>
            <div>
                <dt class="text-xs text-gray-500">Perkiraan lokasi</dt>
                <dd class="mt-0.5 text-primary-900">
                    {{ $laporan->alamat ?? 'Tidak dicantumkan' }}
                </dd>
            </div>
        </dl>

        <p class="mt-4 text-xs text-gray-500">
            Titik koordinat tepat dan identitas pelapor tidak ditampilkan di halaman publik.
        </p>
    </section>

    {{-- Foto --}}
    @if ($laporan->foto->isNotEmpty())
        <section class="mt-8" aria-labelledby="judul-foto">
            <h2 id="judul-foto" class="text-sm font-semibold text-primary-900">Foto dari pelapor</h2>

            <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3">
                @foreach ($laporan->foto as $foto)
                    <a href="{{ $foto->url() }}" target="_blank" rel="noopener"
                       class="overflow-hidden rounded-xl bg-gray-100">
                        <img src="{{ $foto->url() }}"
                             alt="Foto laporan {{ $laporan->tiket }} nomor {{ $loop->iteration }}"
                             loading="lazy" class="aspect-4/3 w-full object-cover">
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Linimasa --}}
    <section class="mt-8" aria-labelledby="judul-linimasa">
        <h2 id="judul-linimasa" class="text-sm font-semibold text-primary-900">Perjalanan penanganan</h2>

        <ol class="relative mt-4 space-y-6 border-l border-gray-200 pl-6">
            <li class="relative">
                <span class="absolute -left-[34px] flex h-5 w-5 items-center justify-center rounded-full bg-gray-200 text-gray-600">
                    <x-ui.ikon nama="megafon" class="h-3 w-3"/>
                </span>
                <p class="text-sm font-medium text-primary-900">Laporan masuk</p>
                <p class="text-xs text-gray-500">{{ $laporan->created_at->translatedFormat('j F Y, H:i') }}</p>
            </li>

            @if ($laporan->diverifikasi_at)
                <li class="relative">
                    <span class="absolute -left-[34px] flex h-5 w-5 items-center justify-center rounded-full bg-primary-100 text-primary-700">
                        <x-ui.ikon nama="centang" class="h-3 w-3"/>
                    </span>
                    <p class="text-sm font-medium text-primary-900">Diverifikasi pemerintah wilayah</p>
                    <p class="text-xs text-gray-500">{{ $laporan->diverifikasi_at->translatedFormat('j F Y, H:i') }}</p>
                </li>
            @endif

            @foreach ($laporan->progres as $progres)
                <li class="relative">
                    <span class="absolute -left-[34px] flex h-5 w-5 items-center justify-center rounded-full bg-blue-100 text-blue-700">
                        <x-ui.ikon nama="jejak" class="h-3 w-3"/>
                    </span>
                    <p class="text-sm font-medium text-primary-900">{{ $progres->status_progres->label() }}</p>
                    <p class="text-xs text-gray-500">{{ $progres->created_at->translatedFormat('j F Y, H:i') }}</p>

                    @if ($progres->catatan)
                        <p class="mt-1.5 text-sm text-gray-600">{{ $progres->catatan }}</p>
                    @endif

                    @if ($progres->urlFotoBukti())
                        <a href="{{ $progres->urlFotoBukti() }}" target="_blank" rel="noopener"
                           class="mt-2 block w-40 overflow-hidden rounded-lg">
                            <img src="{{ $progres->urlFotoBukti() }}" alt="Foto bukti pengerjaan"
                                 loading="lazy" class="w-full object-cover">
                        </a>
                    @endif
                </li>
            @endforeach

            @foreach ($laporan->tindakLanjut as $catatan)
                <li class="relative">
                    <span class="absolute -left-[34px] flex h-5 w-5 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                        <x-ui.ikon nama="jejak" class="h-3 w-3"/>
                    </span>
                    <p class="text-sm font-medium text-primary-900">
                        Diteruskan ke {{ $catatan->nama_dinas }}
                    </p>
                    <p class="text-xs text-gray-500">
                        {{ $catatan->tanggal_kontak?->translatedFormat('j F Y') }}
                    </p>
                    <p class="mt-1.5 text-sm text-gray-600">{{ $catatan->hasil }}</p>
                </li>
            @endforeach

            @if ($laporan->selesai_at)
                <li class="relative">
                    <span class="absolute -left-[34px] flex h-5 w-5 items-center justify-center rounded-full bg-primary-500 text-white">
                        <x-ui.ikon nama="centang" class="h-3 w-3"/>
                    </span>
                    <p class="text-sm font-medium text-primary-900">Dinyatakan selesai</p>
                    <p class="text-xs text-gray-500">{{ $laporan->selesai_at->translatedFormat('j F Y, H:i') }}</p>
                </li>
            @elseif ($laporan->status === StatusLaporan::Ditolak)
                <li class="relative">
                    <span class="absolute -left-[34px] flex h-5 w-5 items-center justify-center rounded-full bg-red-100 text-red-700">
                        <x-ui.ikon nama="silang" class="h-3 w-3"/>
                    </span>
                    <p class="text-sm font-medium text-primary-900">Ditolak</p>
                    <p class="text-xs text-gray-500">Alasannya disampaikan langsung kepada pelapor.</p>
                </li>
            @endif
        </ol>
    </section>

    <div class="mt-10 rounded-2xl bg-primary-50/60 p-5">
        <p class="text-sm text-primary-900">
            Menemukan masalah serupa di sekitar Anda?
            Laporkan lewat aplikasi Resikita di ponsel, cukup foto, dan titiknya terkunci otomatis.
        </p>
    </div>
</div>
