@php
    use App\Support\Rupiah;
    $awalan = \App\Support\Navigasi::awalanRoute(auth()->user());
@endphp

<div>
    <x-ui.kepala-halaman
        judul="Dasbor {{ $wilayah?->namaLengkap() ?? 'Wilayah' }}"
        keterangan="Ringkasan penanganan laporan dan dampak pengelolaan sampah dalam cakupan kewenangan Anda.">
        <x-slot:aksi>
            <div class="inline-flex rounded-xl border border-gray-300 bg-white p-1" role="group" aria-label="Rentang waktu">
                @foreach ([7 => '7 hari', 30 => '30 hari', 90 => '90 hari', 365 => '1 tahun'] as $hari => $teks)
                    <button type="button" wire:click="ubahRentang({{ $hari }})"
                            @if ($rentang === $hari) aria-current="true" @endif
                            class="rounded-lg px-3 py-1.5 text-xs font-medium transition
                                   {{ $rentang === $hari ? 'bg-primary-500 text-white' : 'text-gray-600 hover:bg-gray-50' }}">
                        {{ $teks }}
                    </button>
                @endforeach
            </div>
        </x-slot:aksi>
    </x-ui.kepala-halaman>

    @if ($wilayah === null)
        <x-ui.kartu>
            <x-ui.kosong
                ikon="peringatan"
                judul="Akun Anda belum terhubung ke wilayah"
                pesan="Cakupan data ditentukan oleh wilayah kewenangan. Selama belum disetel, tidak ada laporan yang bisa ditampilkan. Hubungi admin Resikita."/>
        </x-ui.kartu>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-ui.statistik label="Laporan masuk" :nilai="number_format($ringkasan['total'])"
                            ikon="megafon" warna="biru"
                            keterangan="Dalam {{ $rentang }} hari terakhir"/>

            <x-ui.statistik label="Masih berjalan" :nilai="number_format($ringkasan['aktif'])"
                            ikon="peringatan" warna="kuning"
                            keterangan="Belum selesai ditangani"/>

            <x-ui.statistik label="Selesai" :nilai="number_format($ringkasan['selesai'])"
                            ikon="centang" warna="primary"
                            keterangan="{{ $ringkasan['total'] > 0
                                ? round($ringkasan['selesai'] / $ringkasan['total'] * 100).'% dari laporan masuk'
                                : 'Belum ada laporan' }}"/>

            <x-ui.statistik label="Rata-rata waktu respons"
                            :nilai="$ringkasan['rata_respons_jam'] !== null ? number_format($ringkasan['rata_respons_jam'], 1) : '—'"
                            :satuan="$ringkasan['rata_respons_jam'] !== null ? 'jam' : null"
                            ikon="grafik" warna="abu"
                            keterangan="Dari laporan masuk sampai dinyatakan selesai"/>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-3">

            {{-- Antrean kerja --}}
            <x-ui.kartu class="lg:col-span-2" padat
                        judul="Menunggu tindakan Anda"
                        keterangan="Laporan baru yang belum diverifikasi dan laporan terverifikasi yang belum diberi petugas.">
                <x-slot:aksi>
                    <x-ui.tombol jenis="kedua" ukuran="kecil" :tautan="route($awalan.'laporan')">
                        Lihat semua
                    </x-ui.tombol>
                </x-slot:aksi>

                @if ($perluTindakan->isEmpty())
                    <x-ui.kosong
                        ikon="centang"
                        judul="Tidak ada yang menunggu"
                        pesan="Semua laporan dalam cakupan Anda sudah diverifikasi dan diteruskan ke petugas."/>
                @else
                    <ul class="divide-y divide-gray-100">
                        @foreach ($perluTindakan as $laporan)
                            <li>
                                <a href="{{ route($awalan.'laporan.detail', $laporan) }}"
                                   class="flex items-start gap-3 px-5 py-4 transition hover:bg-gray-50">
                                    <span class="flex h-10 w-10 flex-none items-center justify-center rounded-xl bg-gray-100 text-gray-500">
                                        <x-ui.ikon nama="megafon"/>
                                    </span>

                                    <span class="min-w-0 flex-1">
                                        <span class="flex flex-wrap items-center gap-2">
                                            <span class="truncate text-sm font-medium text-primary-900">{{ $laporan->judul }}</span>
                                            <x-ui.lencana :status="$laporan->status"/>
                                        </span>
                                        <span class="mt-0.5 block truncate text-xs text-gray-500">
                                            {{ $laporan->tiket }} &middot; {{ $laporan->kategori?->nama }}
                                            @if ($laporan->desa) &middot; {{ $laporan->desa->nama }} @endif
                                        </span>
                                    </span>

                                    <span class="flex-none text-xs text-gray-400">
                                        {{ $laporan->created_at->diffForHumans(short: true) }}
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.kartu>

            {{-- Sebaran status --}}
            <x-ui.kartu judul="Sebaran status" keterangan="Seluruh laporan dalam cakupan, tanpa batas waktu.">
                <ul class="space-y-3">
                    @php $totalStatus = max(1, array_sum($perStatus)); @endphp
                    @foreach (\App\Enums\StatusLaporan::cases() as $status)
                        @php $jumlah = $perStatus[$status->value] ?? 0; @endphp
                        <li>
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <span class="text-gray-600">{{ $status->label() }}</span>
                                <span class="font-semibold text-primary-900">{{ number_format($jumlah) }}</span>
                            </div>
                            <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-gray-100">
                                <div class="h-full rounded-full bg-primary-500"
                                     style="width: {{ round($jumlah / $totalStatus * 100) }}%"></div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </x-ui.kartu>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">

            {{-- Perbandingan wilayah anak --}}
            @if ($perWilayah !== [])
                <x-ui.kartu padat judul="Sebaran per wilayah"
                            keterangan="Membandingkan beban dan penyelesaian antar wilayah di bawah cakupan Anda.">
                    <x-ui.tabel :kepala="['Wilayah', 'Masuk', 'Selesai', 'Tuntas']">
                        @foreach (array_slice($perWilayah, 0, 8) as $baris)
                            <tr>
                                <td class="px-4 py-3 font-medium text-primary-900">{{ $baris['wilayah'] }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ number_format($baris['jumlah']) }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ number_format($baris['selesai']) }}</td>
                                <td class="px-4 py-3">
                                    <x-ui.lencana
                                        :warna="$baris['jumlah'] > 0 && $baris['selesai'] / $baris['jumlah'] >= 0.7 ? 'hijau' : 'kuning'"
                                        :label="$baris['jumlah'] > 0 ? round($baris['selesai'] / $baris['jumlah'] * 100).'%' : '—'"/>
                                </td>
                            </tr>
                        @endforeach
                    </x-ui.tabel>
                </x-ui.kartu>
            @endif

            {{-- Dampak bank sampah --}}
            <x-ui.kartu judul="Dampak pengelolaan sampah"
                        keterangan="Sampah yang dialihkan dari TPA lewat bank sampah dalam cakupan Anda.">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <p class="text-xs font-medium text-gray-500">Berat teralihkan</p>
                        <p class="mt-1 text-2xl font-bold text-primary-900">
                            {{ number_format($dampak['total_berat_kg'], 1, ',', '.') }}
                            <span class="text-sm font-medium text-gray-500">kg</span>
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500">Nilai kembali ke warga</p>
                        <p class="mt-1 text-2xl font-bold text-primary-900">{{ Rupiah::ringkas($dampak['total_nilai']) }}</p>
                    </div>
                </div>

                @if ($dampak['per_kategori'] !== [])
                    <ul class="mt-5 space-y-2 border-t border-gray-100 pt-4">
                        @foreach ($dampak['per_kategori'] as $baris)
                            <li class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">{{ $baris['kategori'] }}</span>
                                <span class="font-medium text-primary-900">
                                    {{ number_format($baris['berat'], 1, ',', '.') }} kg
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-4 border-t border-gray-100 pt-4 text-sm text-gray-500">
                        Belum ada setoran selesai dari bank sampah di wilayah ini.
                    </p>
                @endif
            </x-ui.kartu>
        </div>

        {{-- Kategori terbanyak --}}
        @if ($perKategori !== [])
            <x-ui.kartu class="mt-6" judul="Kategori laporan terbanyak"
                        keterangan="Persoalan yang paling sering dilaporkan warga di wilayah Anda.">
                <ul class="space-y-3">
                    @php $tertinggi = max(array_column($perKategori, 'jumlah')); @endphp
                    @foreach ($perKategori as $baris)
                        <li>
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <span class="truncate text-gray-600">{{ $baris['kategori'] }}</span>
                                <span class="flex-none font-semibold text-primary-900">{{ number_format($baris['jumlah']) }}</span>
                            </div>
                            <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-gray-100">
                                <div class="h-full rounded-full bg-primary-500"
                                     style="width: {{ round($baris['jumlah'] / max(1, $tertinggi) * 100) }}%"></div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </x-ui.kartu>
        @endif
    @endif
</div>
