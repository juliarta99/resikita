@php use App\Support\Rupiah; @endphp

<div>
    <x-ui.kepala-halaman
        judul="Analitik wilayah"
        keterangan="Tren penanganan laporan dan dampak pengelolaan sampah di {{ $wilayah?->namaLengkap() ?? 'wilayah Anda' }}.">
        <x-slot:aksi>
            <x-ui.pilihan wire:model.live="bulan" class="w-auto" :opsi="[
                6 => '6 bulan terakhir',
                12 => '12 bulan terakhir',
                24 => '24 bulan terakhir',
            ]" aria-label="Rentang tren"/>
        </x-slot:aksi>
    </x-ui.kepala-halaman>

    {{-- Tren bulanan --}}
    <x-ui.kartu judul="Tren laporan bulanan"
                keterangan="Batang penuh adalah laporan masuk; bagian gelap adalah yang sudah selesai ditangani.">
        @if ($tren === [])
            <x-ui.kosong judul="Belum ada data tren"
                         pesan="Tren muncul setelah ada laporan masuk di wilayah Anda."/>
        @else
            @php $puncak = max(array_column($tren, 'jumlah')) ?: 1; @endphp

            <div class="overflow-x-auto">
                <div class="flex min-w-max items-end gap-3 px-1 pt-4" style="height: 220px">
                    @foreach ($tren as $bulanan)
                        @php
                            $tinggi = round($bulanan['jumlah'] / $puncak * 100);
                            $tinggiSelesai = $bulanan['jumlah'] > 0
                                ? round($bulanan['selesai'] / $bulanan['jumlah'] * 100)
                                : 0;
                        @endphp
                        <div class="flex w-14 flex-none flex-col items-center gap-2">
                            <span class="text-xs font-semibold text-primary-900">{{ $bulanan['jumlah'] }}</span>

                            <div class="flex w-full flex-1 items-end">
                                <div class="relative w-full rounded-t-lg bg-primary-100"
                                     style="height: {{ max($tinggi, 2) }}%"
                                     role="img"
                                     aria-label="{{ $bulanan['periode'] }}: {{ $bulanan['jumlah'] }} laporan masuk, {{ $bulanan['selesai'] }} selesai">
                                    <div class="absolute inset-x-0 bottom-0 rounded-t-lg bg-primary-500"
                                         style="height: {{ $tinggiSelesai }}%"></div>
                                </div>
                            </div>

                            <span class="text-[11px] text-gray-500">
                                {{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $bulanan['periode'])->translatedFormat('M y') }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </x-ui.kartu>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">

        {{-- Peta sebaran --}}
        <x-ui.kartu class="lg:col-span-2" padat
                    judul="Peta sebaran laporan aktif"
                    keterangan="{{ count($titik) }} titik laporan yang masih berjalan di wilayah Anda.">
            @if ($titik === [])
                <x-ui.kosong ikon="peta" judul="Tidak ada laporan aktif"
                             pesan="Semua laporan di wilayah Anda sudah selesai ditangani."/>
            @else
                <div wire:ignore
                     x-data="petaLaporan(@js($titik))"
                     x-init="gambar()"
                     class="h-96 w-full rounded-b-2xl"
                     role="application"
                     aria-label="Peta sebaran laporan aktif">
                    <div x-ref="peta" class="h-full w-full rounded-b-2xl"></div>
                </div>

                {{-- Daftar setara peta, untuk pengguna pembaca layar --}}
                <details class="border-t border-gray-100 px-5 py-3">
                    <summary class="cursor-pointer text-sm font-medium text-primary-700">
                        Tampilkan titik sebagai daftar
                    </summary>
                    <ul class="mt-3 max-h-60 space-y-2 overflow-y-auto text-sm">
                        @foreach ($titik as $t)
                            <li class="flex items-center justify-between gap-3">
                                <span class="truncate text-gray-700">{{ $t['judul'] }}</span>
                                <span class="flex-none font-mono text-xs text-gray-400">{{ $t['tiket'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </details>
            @endif
        </x-ui.kartu>

        {{-- Dampak --}}
        <x-ui.kartu judul="Dampak terukur"
                    keterangan="Sampah yang tidak jadi berakhir di TPA, sepanjang waktu.">
            <div class="space-y-4">
                <div>
                    <p class="text-xs font-medium text-gray-500">Berat teralihkan</p>
                    <p class="mt-1 text-2xl font-bold text-primary-900">
                        {{ number_format($dampak['total_berat_kg'], 1, ',', '.') }}
                        <span class="text-sm font-medium text-gray-500">kg</span>
                    </p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500">Nilai kembali ke warga</p>
                    <p class="mt-1 text-xl font-bold text-primary-900">{{ Rupiah::format($dampak['total_nilai']) }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500">Transaksi setoran</p>
                    <p class="mt-1 text-xl font-bold text-primary-900">{{ number_format($dampak['jumlah_transaksi']) }}</p>
                </div>
            </div>

            @if ($dampak['per_kategori'] !== [])
                <ul class="mt-5 space-y-2 border-t border-gray-100 pt-4 text-sm">
                    @foreach ($dampak['per_kategori'] as $baris)
                        <li class="flex items-center justify-between">
                            <span class="text-gray-600">{{ $baris['kategori'] }}</span>
                            <span class="font-medium text-primary-900">
                                {{ number_format($baris['berat'], 1, ',', '.') }} kg
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.kartu>
    </div>

    {{-- Rekomendasi AI --}}
    <x-ui.kartu class="mt-6"
                judul="Rekomendasi prioritas"
                keterangan="Disusun dari angka di halaman ini. Bukan pengganti pertimbangan Anda.">
        <x-slot:aksi>
            <x-ui.tombol jenis="halus" ukuran="kecil" wire:click="susunRekomendasi"
                         wire:loading.attr="disabled" wire:target="susunRekomendasi">
                <span wire:loading.remove wire:target="susunRekomendasi">
                    {{ $rekomendasi ? 'Susun ulang' : 'Susun rekomendasi' }}
                </span>
                <span wire:loading wire:target="susunRekomendasi">Menyusun…</span>
            </x-ui.tombol>
        </x-slot:aksi>

        <div wire:loading.remove wire:target="susunRekomendasi">
            @if ($rekomendasi)
                <div class="ai-rec whitespace-pre-line">{{ $rekomendasi->konten }}</div>

                <p class="mt-4 flex flex-wrap items-center gap-x-2 gap-y-1 border-t border-gray-100 pt-3 text-xs text-gray-400">
                    <x-ui.lencana warna="ungu" label="Dibuat AI"/>
                    Disusun {{ $rekomendasi->created_at->translatedFormat('j F Y, H:i') }}
                    @if (data_get($rekomendasi->raw_response, 'model_version'))
                        &middot; model {{ data_get($rekomendasi->raw_response, 'model_version') }}
                    @endif
                </p>
            @else
                <x-ui.kosong
                    ikon="grafik"
                    judul="Belum ada rekomendasi untuk bulan ini"
                    pesan="Rekomendasi disusun dari data 90 hari terakhir di wilayah Anda, lalu disimpan agar bisa ditelusuri kembali."/>
            @endif
        </div>

        <div wire:loading wire:target="susunRekomendasi" class="space-y-2 py-4" role="status" aria-live="polite">
            <span class="sr-only">Sedang menyusun rekomendasi</span>
            @foreach (range(1, 4) as $i)
                <div class="h-3 animate-pulse rounded bg-gray-100" style="width: {{ [100, 92, 96, 70][$i - 1] }}%"></div>
            @endforeach
        </div>
    </x-ui.kartu>

    {{-- Perbandingan wilayah --}}
    @if ($perWilayah !== [])
        <x-ui.kartu class="mt-6" padat judul="Kinerja per wilayah"
                    keterangan="Membandingkan beban laporan dan tingkat penyelesaian antar wilayah di bawah cakupan Anda.">
            <x-ui.tabel :kepala="['Wilayah', 'Laporan masuk', 'Selesai', 'Tingkat penyelesaian']">
                @foreach ($perWilayah as $baris)
                    @php $persen = $baris['jumlah'] > 0 ? round($baris['selesai'] / $baris['jumlah'] * 100) : 0; @endphp
                    <tr>
                        <td class="px-4 py-3 font-medium text-primary-900">{{ $baris['wilayah'] }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ number_format($baris['jumlah']) }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ number_format($baris['selesai']) }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <div class="h-1.5 w-24 overflow-hidden rounded-full bg-gray-100">
                                    <div class="h-full rounded-full {{ $persen >= 70 ? 'bg-primary-500' : 'bg-amber-400' }}"
                                         style="width: {{ $persen }}%"></div>
                                </div>
                                <span class="text-xs font-medium text-gray-600">{{ $persen }}%</span>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-ui.tabel>
        </x-ui.kartu>
    @endif
</div>
