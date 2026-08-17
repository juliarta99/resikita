@php use App\Support\Rupiah; @endphp

<div>
    @if ($bankSampah === null)
        <x-ui.kepala-halaman judul="Dasbor bank sampah"/>
        <x-ui.kartu>
            <x-ui.kosong
                ikon="peringatan"
                judul="Akun Anda belum terhubung ke unit bank sampah"
                pesan="Tanpa keterkaitan itu, tidak ada katalog harga maupun transaksi yang bisa ditampilkan. Hubungi admin Resikita."/>
        </x-ui.kartu>
    @else
        <x-ui.kepala-halaman
            judul="{{ $bankSampah->nama }}"
            keterangan="{{ $bankSampah->alamat ?? 'Alamat belum diisi' }}">
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
                <x-ui.tombol :tautan="route('bank-sampah.setoran')" ikon="timbangan">Catat setoran</x-ui.tombol>
            </x-slot:aksi>
        </x-ui.kepala-halaman>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-ui.statistik label="Transaksi" :nilai="number_format($rekap['jumlah_transaksi'])"
                            ikon="timbangan" warna="primary"
                            keterangan="Dalam {{ $rentang }} hari terakhir"/>

            <x-ui.statistik label="Sampah diterima" :nilai="number_format($rekap['total_berat'], 1, ',', '.')"
                            satuan="kg" ikon="kotak" warna="biru"
                            keterangan="Teralihkan dari TPA"/>

            <x-ui.statistik label="Nilai dibayarkan" :nilai="Rupiah::ringkas($rekap['total_nilai'])"
                            ikon="dompet" warna="kuning"
                            keterangan="Masuk ke saldo nasabah"/>

            <x-ui.statistik label="Nasabah aktif" :nilai="number_format($rekap['jumlah_nasabah'])"
                            ikon="orang" warna="abu"
                            keterangan="Menyetor pada rentang ini"/>
        </div>

        @if ($jumlahHarga === 0)
            <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-5">
                <div class="flex flex-wrap items-start gap-3">
                    <x-ui.ikon nama="peringatan" class="h-5 w-5 flex-none text-amber-600"/>
                    <p class="flex-1 text-sm text-amber-900">
                        Katalog harga Anda masih kosong. Setoran tidak bisa dicatat sebelum ada
                        minimal satu jenis sampah beserta harganya.
                    </p>
                    <x-ui.tombol ukuran="kecil" :tautan="route('bank-sampah.harga')">Isi katalog harga</x-ui.tombol>
                </div>
            </div>
        @endif

        <div class="mt-6 grid gap-6 lg:grid-cols-3">

            {{-- Setoran berjalan --}}
            <x-ui.kartu padat class="lg:col-span-2"
                        judul="Setoran sedang ditimbang"
                        keterangan="Transaksi yang sudah dibuka tapi belum diselesaikan. Saldo nasabah belum bertambah sampai diselesaikan.">
                @if ($sedangProses->isEmpty())
                    <x-ui.kosong ikon="centang" judul="Tidak ada yang menggantung"
                                 pesan="Semua setoran sudah diselesaikan atau dibatalkan."/>
                @else
                    <ul class="divide-y divide-gray-100">
                        @foreach ($sedangProses as $setoran)
                            <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-primary-900">
                                        {{ $setoran->nasabah?->name ?? 'Nasabah terhapus' }}
                                    </p>
                                    <p class="font-mono text-xs text-gray-500">{{ $setoran->kode_setoran }}</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-sm text-gray-600">
                                        {{ number_format((float) $setoran->total_berat, 2, ',', '.') }} kg
                                        &middot; {{ Rupiah::format($setoran->total_nilai) }}
                                    </span>
                                    <x-ui.tombol jenis="kedua" ukuran="kecil"
                                                 :tautan="route('bank-sampah.setoran', ['setoran' => $setoran->id])">
                                        Lanjutkan
                                    </x-ui.tombol>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.kartu>

            {{-- Dampak kumulatif --}}
            <x-ui.kartu judul="Sejak unit ini berdiri">
                <dl class="space-y-4">
                    <div>
                        <dt class="text-xs font-medium text-gray-500">Total sampah diterima</dt>
                        <dd class="mt-1 text-2xl font-bold text-primary-900">
                            {{ number_format($rekapTotal['total_berat'], 1, ',', '.') }}
                            <span class="text-sm font-medium text-gray-500">kg</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500">Total nilai ke warga</dt>
                        <dd class="mt-1 text-xl font-bold text-primary-900">
                            {{ Rupiah::format($rekapTotal['total_nilai']) }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500">Warga yang pernah menyetor</dt>
                        <dd class="mt-1 text-xl font-bold text-primary-900">
                            {{ number_format($rekapTotal['jumlah_nasabah']) }}
                        </dd>
                    </div>
                </dl>
            </x-ui.kartu>
        </div>

        {{-- Riwayat --}}
        <x-ui.kartu class="mt-6" padat judul="Setoran terakhir">
            <x-slot:aksi>
                <x-ui.tombol jenis="kedua" ukuran="kecil" :tautan="route('bank-sampah.riwayat')">
                    Riwayat lengkap
                </x-ui.tombol>
            </x-slot:aksi>

            @if ($terakhir->isEmpty())
                <x-ui.kosong ikon="timbangan" judul="Belum ada setoran selesai"
                             pesan="Setoran pertama Anda akan tampil di sini."/>
            @else
                <x-ui.tabel :kepala="['Kode', 'Nasabah', 'Berat', 'Nilai', 'Waktu']">
                    @foreach ($terakhir as $setoran)
                        <tr>
                            <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $setoran->kode_setoran }}</td>
                            <td class="px-4 py-3 font-medium text-primary-900">
                                {{ $setoran->nasabah?->name ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ number_format((float) $setoran->total_berat, 2, ',', '.') }} kg
                            </td>
                            <td class="px-4 py-3 font-medium text-primary-900">
                                {{ Rupiah::format($setoran->total_nilai) }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-xs text-gray-500">
                                {{ $setoran->created_at->translatedFormat('j M Y, H:i') }}
                            </td>
                        </tr>
                    @endforeach
                </x-ui.tabel>
            @endif
        </x-ui.kartu>
    @endif
</div>
