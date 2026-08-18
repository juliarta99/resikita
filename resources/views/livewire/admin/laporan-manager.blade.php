@php use App\Enums\StatusLaporan; @endphp

<div>
    <x-ui.kepala-halaman
        judul="Moderasi laporan"
        keterangan="Seluruh laporan yang masuk ke Resikita, lintas wilayah."/>

    <x-ui.kartu padat class="mb-6">
        <div class="grid gap-3 p-5 sm:grid-cols-2 lg:grid-cols-4">
            <x-ui.bidang label="Cari" untuk="cari-mod">
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <x-ui.ikon nama="cari" class="h-4 w-4"/>
                    </span>
                    <x-ui.isian id="cari-mod" wire:model.live.debounce.400ms="cari" class="pl-9"
                                placeholder="Judul, tiket, atau alamat"/>
                </div>
            </x-ui.bidang>

            <x-ui.bidang label="Status" untuk="status-mod">
                <x-ui.pilihan id="status-mod" wire:model.live="status" kosong="Semua status" :opsi="$statusTersedia"/>
            </x-ui.bidang>

            <x-ui.bidang label="Kategori" untuk="kategori-mod">
                <x-ui.pilihan id="kategori-mod" wire:model.live="kategoriId" kosong="Semua kategori"
                              :opsi="$kategoriTersedia->all()"/>
            </x-ui.bidang>

            <x-ui.bidang label="Dasar penunjukan" untuk="routing-mod">
                <x-ui.pilihan id="routing-mod" wire:model.live="alasanRouting" kosong="Semua"
                              :opsi="$routingTersedia"/>
            </x-ui.bidang>
        </div>
    </x-ui.kartu>

    <x-ui.kartu padat>
        @if ($daftar->isEmpty())
            <x-ui.kosong ikon="megafon" judul="Tidak ada laporan"
                         pesan="Tidak ada laporan yang cocok dengan penyaring ini."/>
        @else
            <x-ui.tabel :kepala="['Laporan', 'Pelapor', 'Wilayah', 'Penanggung jawab', 'Status', '']">
                @foreach ($daftar as $laporan)
                    <tr>
                        <td class="px-4 py-3">
                            <p class="font-medium text-primary-900">{{ $laporan->judul }}</p>
                            <p class="font-mono text-xs text-gray-500">
                                {{ $laporan->tiket }} &middot; {{ $laporan->created_at->translatedFormat('j M Y') }}
                            </p>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $laporan->pelapor?->name ?? '—' }}</td>
                        <td class="max-w-xs px-4 py-3 text-gray-600">
                            <p class="truncate">
                                {{ collect([$laporan->desa?->nama, $laporan->kabupaten?->nama])
                                    ->filter()->implode(', ') ?: '—' }}
                            </p>
                        </td>
                        <td class="px-4 py-3">
                            @if ($laporan->alasan_routing)
                                <x-ui.lencana warna="abu" :label="$laporan->alasan_routing->label()"/>
                            @else
                                <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3"><x-ui.lencana :status="$laporan->status"/></td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            @unless ($laporan->status->isFinal())
                                <x-ui.tombol jenis="bahaya" ukuran="kecil"
                                             wire:click="bukaFormTolak({{ $laporan->id }})">
                                    Tolak
                                </x-ui.tombol>
                            @endunless
                        </td>
                    </tr>

                    @if ($tolakId === $laporan->id)
                        <tr class="bg-red-50">
                            <td colspan="6" class="px-4 py-4">
                                <form wire:submit="tolak" class="space-y-3">
                                    <x-ui.bidang label="Alasan penolakan" untuk="tolak-mod-{{ $laporan->id }}"
                                                 :wajib="true"
                                                 petunjuk="Pelapor membaca alasan ini."
                                                 :galat="$errors->first('alasanTolak')">
                                        <textarea id="tolak-mod-{{ $laporan->id }}" wire:model="alasanTolak" rows="2"
                                                  class="block w-full rounded-xl border border-red-200 bg-white px-3.5
                                                         py-2.5 text-sm focus:border-red-400 focus:outline-none
                                                         focus:ring-4 focus:ring-red-100"></textarea>
                                    </x-ui.bidang>

                                    <div class="flex gap-2">
                                        <x-ui.tombol tipe="submit" jenis="bahaya" ukuran="kecil">
                                            Tolak laporan
                                        </x-ui.tombol>
                                        <x-ui.tombol jenis="polos" ukuran="kecil" wire:click="$set('tolakId', null)">
                                            Batal
                                        </x-ui.tombol>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @endif
                @endforeach
            </x-ui.tabel>

            <div class="border-t border-gray-100 px-5 py-3">{{ $daftar->links() }}</div>
        @endif
    </x-ui.kartu>
</div>
