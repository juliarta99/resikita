@php use App\Support\Rupiah; @endphp

<div>
    <x-ui.kepala-halaman
        judul="Nasabah"
        keterangan="Warga yang pernah menyetor sampah di unit Anda."/>

    @if ($daftar === null)
        <x-ui.kartu>
            <x-ui.kosong ikon="peringatan" judul="Akun belum terhubung ke unit bank sampah"
                         pesan="Hubungi admin Resikita untuk menghubungkan akun Anda."/>
        </x-ui.kartu>
    @else
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div class="max-w-sm flex-1">
                <label for="cari-nasabah" class="sr-only">Cari nasabah</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <x-ui.ikon nama="cari" class="h-4 w-4"/>
                    </span>
                    <x-ui.isian id="cari-nasabah" wire:model.live.debounce.400ms="cari" class="pl-9"
                                placeholder="Nama, email, atau kode QR"/>
                </div>
            </div>

            <p class="text-sm text-gray-500">
                {{ number_format($totalNasabah) }} warga pernah menyetor di sini
            </p>
        </div>

        <x-ui.kartu padat>
            @if ($daftar->isEmpty())
                <x-ui.kosong
                    ikon="orang"
                    judul="Belum ada nasabah"
                    pesan="Daftar ini terisi sendiri setelah setoran pertama diselesaikan."/>
            @else
                <x-ui.tabel :kepala="['Nasabah', 'Setoran', 'Total berat', 'Total nilai', 'Terakhir menyetor']">
                    @foreach ($daftar as $nasabah)
                        <tr>
                            <td class="px-4 py-3">
                                <p class="font-medium text-primary-900">{{ $nasabah->name }}</p>
                                <p class="text-xs text-gray-500">{{ $nasabah->email }}</p>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ number_format($nasabah->jumlah_setoran) }}x</td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ number_format((float) $nasabah->total_berat, 2, ',', '.') }} kg
                            </td>
                            <td class="px-4 py-3 font-medium text-primary-900">
                                {{ Rupiah::format((int) $nasabah->total_nilai) }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-xs text-gray-500">
                                {{ $nasabah->setoran_terakhir
                                    ? \Illuminate\Support\Carbon::parse($nasabah->setoran_terakhir)->translatedFormat('j M Y')
                                    : '—' }}
                            </td>
                        </tr>
                    @endforeach
                </x-ui.tabel>

                <div class="border-t border-gray-100 px-5 py-3">{{ $daftar->links() }}</div>
            @endif
        </x-ui.kartu>
    @endif
</div>
