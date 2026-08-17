@php use App\Support\Rupiah; @endphp

<div>
    @if ($umkm === null)
        <x-ui.kepala-halaman judul="Dasbor UMKM"/>
        <x-ui.kartu>
            <x-ui.kosong
                ikon="peringatan"
                judul="Akun Anda belum terhubung ke UMKM"
                pesan="Produk, pesanan, dan saldo melekat pada entitas UMKM. Hubungi admin Resikita."/>
        </x-ui.kartu>
    @else
        <x-ui.kepala-halaman
            judul="{{ $umkm->nama }}"
            keterangan="{{ $umkm->alamat ?? 'Alamat belum diisi' }}">
            <x-slot:aksi>
                <x-ui.lencana :status="$umkm->status"/>
                @if (! $umkm->is_verified)
                    <x-ui.lencana warna="kuning" label="Belum diverifikasi"/>
                @endif
            </x-slot:aksi>
        </x-ui.kepala-halaman>

        @if ($umkm->status !== \App\Enums\StatusUmkm::Aktif)
            <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-5">
                <div class="flex items-start gap-3">
                    <x-ui.ikon nama="info" class="h-5 w-5 flex-none text-amber-600"/>
                    <p class="text-sm text-amber-900">
                        Toko Anda belum aktif, sehingga produknya belum muncul di marketplace.
                        Verifikasi dilakukan admin Resikita sebelum toko bisa berjualan, itu perlindungan
                        bagi pembeli, bukan sekadar kelengkapan data.
                    </p>
                </div>
            </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-ui.statistik label="Perlu dikemas" :nilai="number_format($perluDikemas)"
                            ikon="keranjang" warna="kuning"
                            keterangan="Sudah dibayar, menunggu Anda"/>

            <x-ui.statistik label="Sedang dikirim" :nilai="number_format($sedangDikirim)"
                            ikon="kotak" warna="biru"
                            keterangan="Menunggu diterima pembeli"/>

            <x-ui.statistik label="Selesai bulan ini" :nilai="number_format($selesaiBulanIni)"
                            ikon="centang" warna="primary"
                            :keterangan="Rupiah::ringkas($pendapatanBulanIni).' pendapatan'"/>

            <x-ui.statistik label="Saldo" :nilai="Rupiah::ringkas($saldo)"
                            ikon="dompet" warna="abu"
                            keterangan="Bisa ditarik ke rekening"/>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-3">

            {{-- Antrean pesanan --}}
            <x-ui.kartu padat class="lg:col-span-2"
                        judul="Pesanan menunggu dikerjakan"
                        keterangan="Diurutkan dari yang paling lama dibayar.">
                <x-slot:aksi>
                    <x-ui.tombol jenis="kedua" ukuran="kecil" :tautan="route('umkm.pesanan')">
                        Semua pesanan
                    </x-ui.tombol>
                </x-slot:aksi>

                @if ($antrean->isEmpty())
                    <x-ui.kosong ikon="centang" judul="Tidak ada antrean"
                                 pesan="Semua pesanan yang dibayar sudah Anda kirim."/>
                @else
                    <ul class="divide-y divide-gray-100">
                        @foreach ($antrean as $pesanan)
                            <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-primary-900">
                                        {{ $pesanan->nama_penerima ?? $pesanan->user?->name }}
                                    </p>
                                    <p class="font-mono text-xs text-gray-500">{{ $pesanan->kode }}</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <x-ui.lencana :status="$pesanan->status"/>
                                    <span class="text-sm font-medium text-primary-900">
                                        {{ Rupiah::format($pesanan->total) }}
                                    </span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.kartu>

            {{-- Stok menipis --}}
            <x-ui.kartu padat judul="Stok menipis" keterangan="Lima buah atau kurang.">
                <x-slot:aksi>
                    <x-ui.tombol jenis="kedua" ukuran="kecil" :tautan="route('umkm.produk')">Produk</x-ui.tombol>
                </x-slot:aksi>

                @if ($stokMenipis->isEmpty())
                    <x-ui.kosong ikon="centang"
                                 judul="{{ $jumlahProduk === 0 ? 'Belum ada produk' : 'Stok aman' }}"
                                 pesan="{{ $jumlahProduk === 0
                                     ? 'Tambahkan produk pertama Anda supaya toko bisa berjualan.'
                                     : 'Tidak ada produk aktif yang stoknya menipis.' }}"/>
                @else
                    <ul class="divide-y divide-gray-100">
                        @foreach ($stokMenipis as $produk)
                            <li class="flex items-center justify-between gap-3 px-5 py-3">
                                <span class="truncate text-sm text-primary-900">{{ $produk->nama }}</span>
                                <x-ui.lencana :warna="$produk->stok === 0 ? 'merah' : 'kuning'"
                                              :label="$produk->stok.' tersisa'"/>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.kartu>
        </div>
    @endif
</div>
