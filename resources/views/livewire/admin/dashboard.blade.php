@php $isSuperAdmin = auth()->user()->hasRole(\App\Enums\Role::SuperAdmin->value); @endphp

<div>
    <x-ui.kepala-halaman
        judul="Dasbor platform"
        keterangan="Kesehatan Resikita secara keseluruhan dan antrean yang menunggu keputusan Anda."/>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.statistik label="Pengguna terdaftar" :nilai="number_format($totalPengguna)"
                        ikon="orang" warna="biru"
                        keterangan="+{{ number_format($penggunaBaruBulanIni) }} bulan ini"/>

        <x-ui.statistik label="Total laporan" :nilai="number_format($totalLaporan)"
                        ikon="megafon" warna="primary"
                        keterangan="{{ number_format($laporanAktif) }} masih berjalan"/>

        <x-ui.statistik label="Wilayah bergabung" :nilai="number_format($wilayahTerverifikasi)"
                        ikon="peta" warna="kuning"
                        keterangan="{{ number_format($wilayahMenunggu) }} wilayah punya laporan tapi belum bergabung"/>

        <x-ui.statistik label="Pemakaian fitur suara" :nilai="$fiturSuara['persen_laporan_suara']"
                        satuan="%" ikon="suara" warna="abu"
                        keterangan="{{ number_format($fiturSuara['artikel_didengarkan']) }} artikel didengarkan"/>
    </div>

    {{-- Antrean persetujuan --}}
    <x-ui.kartu class="mt-6" judul="Menunggu keputusan Anda"
                keterangan="Antrean yang menahan orang lain sampai Anda memutuskan.">
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @php
                $antrean = array_filter([
                    $isSuperAdmin ? [
                        'label' => 'Pengajuan wilayah',
                        'jumlah' => $pengajuanMenunggu,
                        'route' => 'admin.pengajuan-wilayah',
                        'ikon' => 'peta',
                    ] : null,
                    [
                        'label' => 'Verifikasi UMKM',
                        'jumlah' => $umkmMenunggu,
                        'route' => 'admin.umkm',
                        'ikon' => 'toko',
                    ],
                    [
                        'label' => 'Penarikan warga',
                        'jumlah' => $penarikanMenunggu,
                        'route' => 'admin.penarikan',
                        'ikon' => 'dompet',
                    ],
                    [
                        'label' => 'Penarikan UMKM',
                        'jumlah' => $penarikanUmkmMenunggu,
                        'route' => 'admin.penarikan',
                        'ikon' => 'dompet',
                    ],
                ]);
            @endphp

            @foreach ($antrean as $item)
                <a href="{{ route($item['route']) }}" wire:navigate
                   class="flex items-center gap-3 rounded-xl border p-4 transition
                          {{ $item['jumlah'] > 0
                              ? 'border-amber-200 bg-amber-50 hover:bg-amber-100'
                              : 'border-gray-200 hover:bg-gray-50' }}">
                    <span class="flex h-10 w-10 flex-none items-center justify-center rounded-xl
                                 {{ $item['jumlah'] > 0 ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-400' }}">
                        <x-ui.ikon :nama="$item['ikon']"/>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-xl font-bold text-primary-900">{{ number_format($item['jumlah']) }}</span>
                        <span class="block truncate text-xs text-gray-600">{{ $item['label'] }}</span>
                    </span>
                </a>
            @endforeach
        </div>
    </x-ui.kartu>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">

        {{-- Laporan terbaru --}}
        <x-ui.kartu padat class="lg:col-span-2" judul="Laporan terbaru"
                    keterangan="Seluruh laporan yang masuk ke sistem, lintas wilayah.">
            <x-slot:aksi>
                <x-ui.tombol jenis="kedua" ukuran="kecil" :tautan="route('admin.laporan')">
                    Moderasi laporan
                </x-ui.tombol>
            </x-slot:aksi>

            @if ($laporanTerbaru->isEmpty())
                <x-ui.kosong ikon="megafon" judul="Belum ada laporan"
                             pesan="Laporan pertama dari warga akan tampil di sini."/>
            @else
                <ul class="divide-y divide-gray-100">
                    @foreach ($laporanTerbaru as $laporan)
                        <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-primary-900">{{ $laporan->judul }}</p>
                                <p class="truncate text-xs text-gray-500">
                                    {{ $laporan->tiket }}
                                    @if ($laporan->kabupaten) &middot; {{ $laporan->kabupaten->nama }} @endif
                                    &middot; {{ $laporan->created_at->diffForHumans(short: true) }}
                                </p>
                            </div>
                            <x-ui.lencana :status="$laporan->status"/>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.kartu>

        {{-- Fitur suara --}}
        <x-ui.kartu judul="Jejak fitur suara"
                    keterangan="Angka yang mengubah klaim inklusivitas menjadi sesuatu yang bisa ditunjukkan.">
            <dl class="space-y-4">
                <div>
                    <dt class="text-xs font-medium text-gray-500">Laporan didiktekan</dt>
                    <dd class="mt-1 text-2xl font-bold text-primary-900">
                        {{ number_format($fiturSuara['laporan_suara']) }}
                        <span class="text-sm font-medium text-gray-500">
                            dari {{ number_format($fiturSuara['laporan_total']) }}
                        </span>
                    </dd>
                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-gray-100">
                        <div class="h-full rounded-full bg-primary-500"
                             style="width: {{ min(100, $fiturSuara['persen_laporan_suara']) }}%"></div>
                    </div>
                </div>

                <div>
                    <dt class="text-xs font-medium text-gray-500">Artikel didengarkan</dt>
                    <dd class="mt-1 text-2xl font-bold text-primary-900">
                        {{ number_format($fiturSuara['artikel_didengarkan']) }}
                    </dd>
                    <dd class="text-xs text-gray-500">
                        dibanding {{ number_format($fiturSuara['artikel_dilihat']) }} kali dibaca
                    </dd>
                </div>
            </dl>
        </x-ui.kartu>
    </div>
</div>
