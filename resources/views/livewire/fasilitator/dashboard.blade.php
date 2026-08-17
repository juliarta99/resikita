<div>
    <x-ui.kepala-halaman
        judul="Dasbor fasilitator wilayah"
        keterangan="Laporan warga dari daerah yang belum bergabung dengan Resikita, dan wilayah mana yang paling mendesak diajak."/>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.statistik label="Laporan belum terjangkau" :nilai="number_format($jumlahLaporan)"
                        ikon="megafon" warna="kuning"
                        keterangan="Dari wilayah tanpa pemerintah terdaftar"/>

        <x-ui.statistik label="Belum ditindaklanjuti" :nilai="number_format($belumDitindaklanjuti)"
                        ikon="peringatan" warna="merah"
                        keterangan="Belum pernah dikontakkan ke dinas mana pun"/>

        <x-ui.statistik label="Wilayah menunggu" :nilai="number_format($wilayahMenunggu)"
                        ikon="peta" warna="biru"
                        keterangan="Punya laporan warga, belum bergabung"/>

        <x-ui.statistik label="Kontak dinas bulan ini" :nilai="number_format($kontakBulanIni)"
                        ikon="jejak" warna="primary"
                        keterangan="Tercatat sejak awal bulan"/>
    </div>

    @if ($jumlahLaporan > 0 && $totalLaporanSistem > 0)
        <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-5">
            <div class="flex items-start gap-3">
                <x-ui.ikon nama="info" class="h-5 w-5 flex-none text-amber-600"/>
                <p class="text-sm text-amber-900">
                    <span class="font-semibold">{{ round($jumlahLaporan / $totalLaporanSistem * 100, 1) }}%</span>
                    dari seluruh laporan yang masuk ke Resikita berasal dari wilayah yang belum bergabung.
                    Angka inilah bukti paling konkret untuk mengajak daerah baru bergabung.
                </p>
            </div>
        </div>
    @endif

    <div class="mt-6 grid gap-6 lg:grid-cols-2">

        {{-- Laporan yang menunggu --}}
        <x-ui.kartu padat judul="Menunggu tindak lanjut"
                    keterangan="Laporan yang belum pernah diteruskan ke dinas.">
            <x-slot:aksi>
                <x-ui.tombol jenis="kedua" ukuran="kecil" :tautan="route('fasilitator.laporan')">
                    Lihat semua
                </x-ui.tombol>
            </x-slot:aksi>

            @if ($laporanTerbaru->isEmpty())
                <x-ui.kosong ikon="centang" judul="Semua sudah diteruskan"
                             pesan="Tidak ada laporan dari wilayah belum terjangkau yang menganggur."/>
            @else
                <ul class="divide-y divide-gray-100">
                    @foreach ($laporanTerbaru as $laporan)
                        <li>
                            <a href="{{ route('fasilitator.laporan.detail', $laporan) }}"
                               class="block px-5 py-4 transition hover:bg-gray-50">
                                <p class="truncate text-sm font-medium text-primary-900">{{ $laporan->judul }}</p>
                                <p class="mt-0.5 truncate text-xs text-gray-500">
                                    {{ $laporan->tiket }}
                                    @if ($laporan->kabupaten) &middot; {{ $laporan->kabupaten->nama }} @endif
                                    &middot; {{ $laporan->created_at->diffForHumans() }}
                                </p>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.kartu>

        {{-- Papan prioritas ringkas --}}
        <x-ui.kartu padat judul="Prioritas perluasan teratas"
                    keterangan="Wilayah dengan tekanan laporan warga tertinggi.">
            <x-slot:aksi>
                <x-ui.tombol jenis="kedua" ukuran="kecil" :tautan="route('fasilitator.prioritas')">
                    Papan lengkap
                </x-ui.tombol>
            </x-slot:aksi>

            @if ($prioritasTeratas->isEmpty())
                <x-ui.kosong ikon="peta" judul="Belum ada wilayah berskor"
                             pesan="Skor prioritas naik setiap kali ada laporan dari wilayah yang belum bergabung."/>
            @else
                <ul class="divide-y divide-gray-100">
                    @foreach ($prioritasTeratas as $wilayah)
                        <li class="flex items-center justify-between gap-3 px-5 py-4">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-primary-900">{{ $wilayah->namaLengkap() }}</p>
                                <p class="truncate text-xs text-gray-500">
                                    {{ $wilayah->parent?->namaLengkap() }} &middot; kode {{ $wilayah->kode }}
                                </p>
                            </div>
                            <x-ui.lencana warna="kuning" :label="$wilayah->skor_prioritas.' poin'"/>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.kartu>
    </div>

    {{-- Riwayat kontak --}}
    <x-ui.kartu class="mt-6" padat judul="Kontak dinas terakhir"
                keterangan="Catatan komunikasi yang menjadi bukti saat mengajak wilayah bergabung.">
        @if ($kontakTerakhir->isEmpty())
            <x-ui.kosong ikon="jejak" judul="Belum ada catatan"
                         pesan="Catat setiap kontak ke dinas dari halaman detail laporan."/>
        @else
            <x-ui.tabel :kepala="['Dinas', 'Laporan', 'Tanggal', 'Fasilitator']">
                @foreach ($kontakTerakhir as $kontak)
                    <tr>
                        <td class="px-4 py-3">
                            <p class="font-medium text-primary-900">{{ $kontak->nama_dinas }}</p>
                            @if ($kontak->kontak_dinas)
                                <p class="text-xs text-gray-500">{{ $kontak->kontak_dinas }}</p>
                            @endif
                        </td>
                        <td class="max-w-xs px-4 py-3">
                            <p class="truncate text-gray-600">{{ $kontak->laporan?->judul ?? '—' }}</p>
                            <p class="font-mono text-xs text-gray-400">{{ $kontak->laporan?->tiket }}</p>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-gray-600">
                            {{ $kontak->tanggal_kontak?->translatedFormat('j M Y') }}
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $kontak->fasilitator?->name ?? '—' }}</td>
                    </tr>
                @endforeach
            </x-ui.tabel>
        @endif
    </x-ui.kartu>
</div>
