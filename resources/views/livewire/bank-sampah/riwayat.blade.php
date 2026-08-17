@php use App\Support\Rupiah; @endphp

<div>
    <x-ui.kepala-halaman
        judul="Riwayat setoran"
        keterangan="Seluruh transaksi unit Anda. Rincian timbangan menyimpan harga yang berlaku saat transaksi, bukan harga hari ini."/>

    @if ($daftar === null)
        <x-ui.kartu>
            <x-ui.kosong ikon="peringatan" judul="Akun belum terhubung ke unit bank sampah"
                         pesan="Hubungi admin Resikita untuk menghubungkan akun Anda."/>
        </x-ui.kartu>
    @else
        <div class="mb-5 grid gap-3 sm:grid-cols-2 lg:max-w-2xl">
            <x-ui.bidang label="Cari" untuk="cari-riwayat">
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <x-ui.ikon nama="cari" class="h-4 w-4"/>
                    </span>
                    <x-ui.isian id="cari-riwayat" wire:model.live.debounce.400ms="cari" class="pl-9"
                                placeholder="Kode setoran atau nama nasabah"/>
                </div>
            </x-ui.bidang>

            <x-ui.bidang label="Status" untuk="status-riwayat">
                <x-ui.pilihan id="status-riwayat" wire:model.live="status" kosong="Semua status"
                              :opsi="$statusTersedia"/>
            </x-ui.bidang>
        </div>

        <x-ui.kartu padat>
            @if ($daftar->isEmpty())
                <x-ui.kosong ikon="jejak" judul="Belum ada transaksi"
                             pesan="Riwayat terisi setelah setoran pertama dicatat."/>
            @else
                <x-ui.tabel :kepala="['Kode', 'Nasabah', 'Jenis', 'Berat', 'Nilai', 'Status', 'Waktu', '']">
                    @foreach ($daftar as $setoran)
                        <tr>
                            <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $setoran->kode_setoran }}</td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-primary-900">{{ $setoran->nasabah?->name ?? '—' }}</p>
                                <p class="text-xs text-gray-400">oleh {{ $setoran->petugas?->name ?? '—' }}</p>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $setoran->item_count }}</td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ number_format((float) $setoran->total_berat, 2, ',', '.') }} kg
                            </td>
                            <td class="px-4 py-3 font-medium text-primary-900">
                                {{ Rupiah::format($setoran->total_nilai) }}
                            </td>
                            <td class="px-4 py-3"><x-ui.lencana :status="$setoran->status"/></td>
                            <td class="whitespace-nowrap px-4 py-3 text-xs text-gray-500">
                                {{ $setoran->created_at->translatedFormat('j M Y, H:i') }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <x-ui.tombol jenis="kedua" ukuran="kecil" wire:click="lihatRincian({{ $setoran->id }})">
                                    {{ $rincianId === $setoran->id ? 'Tutup' : 'Rincian' }}
                                </x-ui.tombol>
                            </td>
                        </tr>

                        @if ($rincianId === $setoran->id && $rincian)
                            <tr class="bg-gray-50">
                                <td colspan="8" class="px-4 py-4">
                                    @if ($rincian->item->isEmpty())
                                        <p class="text-sm text-gray-500">Tidak ada item pada transaksi ini.</p>
                                    @else
                                        <table class="min-w-full text-sm">
                                            <thead>
                                                <tr class="text-left text-xs uppercase tracking-wide text-gray-500">
                                                    <th scope="col" class="py-2 pr-4">Jenis saat itu</th>
                                                    <th scope="col" class="py-2 pr-4">Berat</th>
                                                    <th scope="col" class="py-2 pr-4">Harga saat itu</th>
                                                    <th scope="col" class="py-2">Subtotal</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200">
                                                @foreach ($rincian->item as $item)
                                                    <tr>
                                                        <td class="py-2 pr-4 text-primary-900">{{ $item->jenis_snapshot }}</td>
                                                        <td class="py-2 pr-4 text-gray-600">
                                                            {{ number_format((float) $item->berat, 2, ',', '.') }} kg
                                                        </td>
                                                        <td class="py-2 pr-4 text-gray-600">
                                                            {{ Rupiah::format($item->harga_snapshot) }}
                                                        </td>
                                                        <td class="py-2 font-medium text-primary-900">
                                                            {{ Rupiah::format($item->subtotal) }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @endif

                                    @if ($rincian->catatan)
                                        <p class="mt-3 rounded-lg bg-white p-3 text-sm text-gray-600 ring-1 ring-gray-200">
                                            {{ $rincian->catatan }}
                                        </p>
                                    @endif
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </x-ui.tabel>

                <div class="border-t border-gray-100 px-5 py-3">{{ $daftar->links() }}</div>
            @endif
        </x-ui.kartu>
    @endif
</div>
