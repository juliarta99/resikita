@php
    $awalan = \App\Support\Navigasi::awalanRoute(auth()->user());
    $bolehVerifikasi = auth()->user()->can('verifikasi', $laporan);
    $bolehTolak = auth()->user()->can('tolak', $laporan);
    $bolehTugaskan = auth()->user()->can('tugaskan', $laporan);
@endphp

<div>
    <a href="{{ route($awalan.'laporan') }}" wire:navigate
       class="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-primary-900">
        <x-ui.ikon nama="keluar" class="h-4 w-4 rotate-180"/>
        Kembali ke daftar laporan
    </a>

    <x-ui.kepala-halaman :judul="$laporan->judul">
        <x-slot:keterangan>
            <span class="font-mono">{{ $laporan->tiket }}</span> &middot;
            dilaporkan {{ $laporan->created_at->translatedFormat('j F Y, H:i') }} WIB
            oleh {{ $laporan->pelapor?->name ?? 'pengguna terhapus' }}
        </x-slot:keterangan>

        <x-slot:aksi>
            <x-ui.lencana :status="$laporan->status"/>
            @if ($laporan->deskripsi_sumber === \App\Enums\SumberInput::Suara)
                <x-ui.lencana warna="nila" label="Didiktekan lewat suara"/>
            @endif
        </x-slot:aksi>
    </x-ui.kepala-halaman>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">

            {{-- Foto --}}
            @if ($laporan->foto->isNotEmpty())
                <x-ui.kartu padat judul="Foto dari pelapor">
                    <div class="grid grid-cols-2 gap-2 p-5 pt-0 sm:grid-cols-3">
                        @foreach ($laporan->foto as $foto)
                            <a href="{{ $foto->url() }}" target="_blank" rel="noopener"
                               class="group relative block overflow-hidden rounded-xl bg-gray-100">
                                <img src="{{ $foto->url() }}"
                                     alt="Foto laporan {{ $laporan->tiket }} nomor {{ $loop->iteration }}"
                                     loading="lazy"
                                     class="aspect-4/3 w-full object-cover transition group-hover:scale-105">
                            </a>
                        @endforeach
                    </div>
                </x-ui.kartu>
            @endif

            {{-- Isi laporan --}}
            <x-ui.kartu judul="Keterangan">
                <p class="whitespace-pre-line text-sm leading-relaxed text-gray-700">{{ $laporan->deskripsi }}</p>

                <dl class="mt-5 grid gap-4 border-t border-gray-100 pt-5 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-medium text-gray-500">Kategori</dt>
                        <dd class="mt-0.5 text-sm text-primary-900">{{ $laporan->kategori?->nama ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500">Alamat</dt>
                        <dd class="mt-0.5 text-sm text-primary-900">{{ $laporan->alamat ?? 'Tidak tersedia' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500">Wilayah</dt>
                        <dd class="mt-0.5 text-sm text-primary-900">
                            {{ collect([
                                $laporan->desa?->nama,
                                $laporan->kecamatan?->nama,
                                $laporan->kabupaten?->nama,
                                $laporan->provinsi?->nama,
                            ])->filter()->implode(', ') ?: 'Belum teridentifikasi' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500">Titik koordinat</dt>
                        <dd class="mt-0.5 text-sm">
                            <a href="https://www.google.com/maps?q={{ $laporan->latitude }},{{ $laporan->longitude }}"
                               target="_blank" rel="noopener"
                               class="font-medium text-primary-700 underline hover:text-primary-900">
                                {{ number_format((float) $laporan->latitude, 6) }},
                                {{ number_format((float) $laporan->longitude, 6) }}
                            </a>
                        </dd>
                    </div>
                </dl>
            </x-ui.kartu>

            {{-- Riwayat penanganan --}}
            <x-ui.kartu judul="Riwayat penanganan"
                        keterangan="Setiap perubahan yang tercatat, dari verifikasi sampai bukti pengerjaan.">
                @if ($laporan->penugasan->isEmpty() && $laporan->progres->isEmpty() && $laporan->diverifikasi_at === null)
                    <x-ui.kosong
                        ikon="jejak"
                        judul="Belum ada riwayat"
                        pesan="Laporan ini belum diverifikasi maupun ditugaskan kepada siapa pun."/>
                @else
                    <ol class="relative space-y-6 border-l border-gray-200 pl-6">
                        @if ($laporan->diverifikasi_at)
                            <li class="relative">
                                <span class="absolute -left-[34px] flex h-5 w-5 items-center justify-center rounded-full bg-primary-100 text-primary-700">
                                    <x-ui.ikon nama="centang" class="h-3 w-3"/>
                                </span>
                                <p class="text-sm font-medium text-primary-900">Diverifikasi</p>
                                <p class="text-xs text-gray-500">
                                    {{ $laporan->verifikator?->name ?? 'Sistem' }} &middot;
                                    {{ $laporan->diverifikasi_at->translatedFormat('j F Y, H:i') }}
                                </p>
                            </li>
                        @endif

                        @foreach ($laporan->penugasan as $tugas)
                            <li class="relative">
                                <span class="absolute -left-[34px] flex h-5 w-5 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                                    <x-ui.ikon nama="orang" class="h-3 w-3"/>
                                </span>
                                <p class="text-sm font-medium text-primary-900">
                                    Ditugaskan kepada {{ $tugas->petugas?->name ?? 'petugas terhapus' }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    oleh {{ $tugas->penugas?->name ?? 'Sistem' }} &middot;
                                    {{ $tugas->ditugaskan_at?->translatedFormat('j F Y, H:i') }}
                                </p>
                                @if ($tugas->catatan)
                                    <p class="mt-1.5 rounded-lg bg-gray-50 p-2.5 text-xs text-gray-600">{{ $tugas->catatan }}</p>
                                @endif
                            </li>
                        @endforeach

                        @foreach ($laporan->progres as $progres)
                            <li class="relative">
                                <span class="absolute -left-[34px] flex h-5 w-5 items-center justify-center rounded-full bg-blue-100 text-blue-700">
                                    <x-ui.ikon nama="jejak" class="h-3 w-3"/>
                                </span>
                                <p class="text-sm font-medium text-primary-900">
                                    {{ $progres->status_progres->label() }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ $progres->petugas?->name ?? 'Petugas' }} &middot;
                                    {{ $progres->created_at->translatedFormat('j F Y, H:i') }}
                                </p>
                                @if ($progres->catatan)
                                    <p class="mt-1.5 text-sm text-gray-600">{{ $progres->catatan }}</p>
                                @endif
                                @if ($progres->urlFotoBukti())
                                    <a href="{{ $progres->urlFotoBukti() }}" target="_blank" rel="noopener"
                                       class="mt-2 block w-40 overflow-hidden rounded-lg">
                                        <img src="{{ $progres->urlFotoBukti() }}"
                                             alt="Bukti pengerjaan oleh {{ $progres->petugas?->name ?? 'petugas' }}"
                                             loading="lazy" class="w-full object-cover">
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                @endif
            </x-ui.kartu>
        </div>

        {{-- Panel tindakan --}}
        <div class="space-y-6">
            <x-ui.kartu judul="Penanggung jawab">
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-xs font-medium text-gray-500">Ditangani oleh</dt>
                        <dd class="mt-0.5 text-primary-900">
                            {{ $laporan->penanggungJawab?->name ?? 'Belum ditentukan' }}
                            @if ($laporan->penanggung_jawab_type)
                                <span class="block text-xs text-gray-500">
                                    {{ $laporan->penanggung_jawab_type->label() }}
                                </span>
                            @endif
                        </dd>
                    </div>

                    @if ($laporan->alasan_routing)
                        <div>
                            <dt class="text-xs font-medium text-gray-500">Dasar penunjukan</dt>
                            <dd class="mt-0.5 text-primary-900">{{ $laporan->alasan_routing->label() }}</dd>
                        </div>
                    @endif

                    @if ($laporan->selesai_at)
                        <div>
                            <dt class="text-xs font-medium text-gray-500">Waktu penanganan</dt>
                            <dd class="mt-0.5 text-primary-900">
                                {{ $laporan->waktuResponsJam() !== null
                                    ? number_format($laporan->waktuResponsJam(), 1, ',', '.').' jam'
                                    : '—' }}
                            </dd>
                        </div>
                    @endif
                </dl>
            </x-ui.kartu>

            @if ($laporan->status->isFinal())
                <x-ui.kartu>
                    <div class="flex items-start gap-3">
                        <x-ui.ikon nama="info" class="h-5 w-5 flex-none text-gray-400"/>
                        <p class="text-sm text-gray-600">
                            Laporan ini sudah berstatus <span class="font-medium">{{ $laporan->status->label() }}</span>
                            dan tidak bisa diubah lagi.
                        </p>
                    </div>
                </x-ui.kartu>
            @elseif ($bolehVerifikasi || $bolehTolak || $bolehTugaskan)
                <x-ui.kartu judul="Tindakan">
                    <div class="space-y-3">
                        @if ($bolehVerifikasi && $laporan->status === \App\Enums\StatusLaporan::Baru)
                            <x-ui.tombol wire:click="verifikasi" class="w-full" ikon="centang"
                                         wire:loading.attr="disabled" wire:target="verifikasi">
                                Verifikasi laporan
                            </x-ui.tombol>
                        @endif

                        @if ($bolehTugaskan && $laporan->status === \App\Enums\StatusLaporan::Diverifikasi)
                            <x-ui.tombol jenis="halus" class="w-full" ikon="orang"
                                         wire:click="$toggle('formTugasTerbuka')">
                                Tugaskan petugas
                            </x-ui.tombol>
                        @endif

                        @if ($bolehTolak)
                            <x-ui.tombol jenis="kedua" class="w-full" ikon="silang"
                                         wire:click="$toggle('formTolakTerbuka')">
                                Tolak laporan
                            </x-ui.tombol>
                        @endif
                    </div>

                    {{-- Formulir penugasan --}}
                    @if ($formTugasTerbuka)
                        <form wire:submit="tugaskan" class="mt-5 space-y-3 border-t border-gray-100 pt-5">
                            <x-ui.bidang label="Petugas" untuk="petugas" :wajib="true"
                                         :galat="$errors->first('petugasId')">
                                @if ($petugasPilihan->isEmpty())
                                    <p class="rounded-xl bg-amber-50 p-3 text-xs text-amber-800">
                                        Belum ada petugas aktif di wilayah Anda. Tambahkan lebih dulu di menu Petugas.
                                    </p>
                                @else
                                    <x-ui.pilihan id="petugas" wire:model="petugasId" kosong="Pilih petugas"
                                                  :opsi="$petugasPilihan->pluck('name', 'id')->all()"
                                                  :galat="$errors->has('petugasId')"/>
                                @endif
                            </x-ui.bidang>

                            <x-ui.bidang label="Catatan untuk petugas" untuk="catatan-tugas"
                                         petunjuk="Opsional. Misalnya alat yang perlu dibawa atau waktu yang disarankan.">
                                <textarea id="catatan-tugas" wire:model="catatanTugas" rows="3"
                                          class="block w-full rounded-xl border border-gray-300 px-3.5 py-2.5 text-sm
                                                 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-4
                                                 focus:ring-primary-100"></textarea>
                            </x-ui.bidang>

                            <div class="flex gap-2">
                                <x-ui.tombol tipe="submit" class="flex-1" :disabled="$petugasPilihan->isEmpty()">
                                    Tugaskan
                                </x-ui.tombol>
                                <x-ui.tombol jenis="polos" wire:click="$set('formTugasTerbuka', false)">
                                    Batal
                                </x-ui.tombol>
                            </div>
                        </form>
                    @endif

                    {{-- Formulir penolakan --}}
                    @if ($formTolakTerbuka)
                        <form wire:submit="tolak" class="mt-5 space-y-3 border-t border-gray-100 pt-5">
                            <x-ui.bidang label="Alasan penolakan" untuk="alasan-tolak" :wajib="true"
                                         petunjuk="Pelapor membaca alasan ini. Jelaskan secukupnya."
                                         :galat="$errors->first('alasanTolak')">
                                <textarea id="alasan-tolak" wire:model="alasanTolak" rows="3"
                                          class="block w-full rounded-xl border px-3.5 py-2.5 text-sm shadow-sm
                                                 focus:outline-none focus:ring-4
                                                 {{ $errors->has('alasanTolak')
                                                     ? 'border-red-300 focus:border-red-500 focus:ring-red-200'
                                                     : 'border-gray-300 focus:border-primary-500 focus:ring-primary-100' }}"></textarea>
                            </x-ui.bidang>

                            <div class="flex gap-2">
                                <x-ui.tombol tipe="submit" jenis="bahaya" class="flex-1">Tolak laporan</x-ui.tombol>
                                <x-ui.tombol jenis="polos" wire:click="$set('formTolakTerbuka', false)">Batal</x-ui.tombol>
                            </div>
                        </form>
                    @endif
                </x-ui.kartu>
            @endif
        </div>
    </div>
</div>
