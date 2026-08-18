<div>
    <x-ui.kepala-halaman
        judul="Papan prioritas perluasan"
        keterangan="Wilayah yang warganya sudah melapor lewat Resikita, tapi pemerintahnya belum bergabung. Urutan berdasarkan tekanan laporan nyata.">
        <x-slot:aksi>
            <x-ui.pilihan wire:model.live="tingkat" kosong="Semua tingkat" :opsi="$tingkatTersedia"
                          class="w-auto" aria-label="Saring tingkat wilayah"/>
        </x-slot:aksi>
    </x-ui.kepala-halaman>

    <x-ui.kartu padat>
        @if ($daftar->isEmpty())
            <x-ui.kosong
                ikon="peta"
                judul="Belum ada wilayah berskor"
                pesan="Skor prioritas naik setiap kali ada laporan dari wilayah yang belum bergabung. Papan ini terisi sendiri seiring laporan masuk."/>
        @else
            <x-ui.tabel :kepala="['Wilayah', 'Induk', 'Kode', 'Status', 'Skor', '']">
                @foreach ($daftar as $wilayah)
                    <tr class="transition hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <p class="font-medium text-primary-900">{{ $wilayah->namaLengkap() }}</p>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $wilayah->parent?->namaLengkap() ?? '—' }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $wilayah->kode }}</td>
                        <td class="px-4 py-3"><x-ui.lencana :status="$wilayah->status_registrasi"/></td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-2">
                                <span class="font-semibold text-primary-900">{{ number_format($wilayah->skor_prioritas) }}</span>
                                <span class="h-1.5 w-16 overflow-hidden rounded-full bg-gray-100">
                                    <span class="block h-full rounded-full bg-amber-400"
                                          style="width: {{ min(100, round($wilayah->skor_prioritas / max(1, $daftar->max('skor_prioritas')) * 100)) }}%"></span>
                                </span>
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            <x-ui.tombol jenis="kedua" ukuran="kecil" wire:click="lihatRingkasan({{ $wilayah->id }})">
                                {{ $wilayahDipilih === $wilayah->id ? 'Tutup' : 'Rincian' }}
                            </x-ui.tombol>
                        </td>
                    </tr>

                    @if ($wilayahDipilih === $wilayah->id && $ringkasan)
                        <tr class="bg-gray-50">
                            <td colspan="6" class="px-4 py-5">
                                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                    <div>
                                        <p class="text-xs text-gray-500">Total laporan warga</p>
                                        <p class="mt-0.5 text-lg font-bold text-primary-900">
                                            {{ number_format($ringkasan['total_laporan']) }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Masih berjalan</p>
                                        <p class="mt-0.5 text-lg font-bold text-primary-900">
                                            {{ number_format($ringkasan['laporan_aktif']) }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Sudah diteruskan ke dinas</p>
                                        <p class="mt-0.5 text-lg font-bold text-primary-900">
                                            {{ number_format($ringkasan['sudah_ditindaklanjuti']) }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Laporan pertama</p>
                                        <p class="mt-0.5 text-sm font-medium text-primary-900">
                                            {{ $ringkasan['laporan_pertama']
                                                ? \Illuminate\Support\Carbon::parse($ringkasan['laporan_pertama'])->translatedFormat('j F Y')
                                                : '—' }}
                                        </p>
                                    </div>
                                </div>

                                <p class="mt-4 rounded-xl bg-white p-3 text-sm text-gray-600 ring-1 ring-gray-200">
                                    Bahan percakapan dengan pemerintah {{ $wilayah->namaLengkap() }}:
                                    <span class="font-medium text-primary-900">
                                        {{ number_format($ringkasan['total_laporan']) }} laporan warga
                                    </span>
                                    sudah masuk lewat Resikita
                                    @if ($ringkasan['laporan_pertama'])
                                        sejak {{ \Illuminate\Support\Carbon::parse($ringkasan['laporan_pertama'])->translatedFormat('F Y') }},
                                    @endif
                                    dan {{ number_format($ringkasan['sudah_ditindaklanjuti']) }} di antaranya
                                    sudah kami teruskan ke dinas terkait.
                                </p>
                            </td>
                        </tr>
                    @endif
                @endforeach
            </x-ui.tabel>

            <div class="border-t border-gray-100 px-5 py-3">{{ $daftar->links() }}</div>
        @endif
    </x-ui.kartu>
</div>
