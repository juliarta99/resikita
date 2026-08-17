@php use App\Support\Rupiah; use App\Enums\StatusPenarikan; @endphp

<div>
    <x-ui.kepala-halaman
        judul="Penarikan saldo"
        keterangan="Saldo pemohon sudah dipotong sejak pengajuan dibuat. Menolak akan mengembalikannya."/>

    <div class="mb-6 flex flex-wrap items-center gap-3">
        <div class="inline-flex rounded-xl border border-gray-300 bg-white p-1" role="group" aria-label="Jenis pemohon">
            @foreach (['warga' => ['Warga', $menungguWarga], 'umkm' => ['UMKM', $menungguUmkm]] as $nilai => $info)
                <button type="button" wire:click="$set('jenis', '{{ $nilai }}')"
                        @if ($jenis === $nilai) aria-current="true" @endif
                        class="flex items-center gap-2 rounded-lg px-3.5 py-1.5 text-sm font-medium transition
                               {{ $jenis === $nilai ? 'bg-primary-500 text-white' : 'text-gray-600 hover:bg-gray-50' }}">
                    {{ $info[0] }}
                    @if ($info[1] > 0)
                        <span class="rounded-full px-1.5 py-0.5 text-[10px] font-semibold
                                     {{ $jenis === $nilai ? 'bg-white/20' : 'bg-amber-100 text-amber-700' }}">
                            {{ $info[1] }}
                        </span>
                    @endif
                </button>
            @endforeach
        </div>

        <x-ui.pilihan wire:model.live="status" kosong="Semua status" :opsi="$statusTersedia"
                      class="w-auto" aria-label="Saring status penarikan"/>
    </div>

    @if ($daftar->isEmpty())
        <x-ui.kartu>
            <x-ui.kosong ikon="dompet" judul="Tidak ada penarikan"
                         pesan="Belum ada pengajuan yang cocok dengan penyaring ini."/>
        </x-ui.kartu>
    @else
        <x-ui.kartu padat>
            <x-ui.tabel :kepala="['Pemohon', 'Jumlah', 'Rekening tujuan', 'Status', 'Diajukan', '']">
                @foreach ($daftar as $tarik)
                    <tr>
                        <td class="px-4 py-3">
                            <p class="font-medium text-primary-900">
                                {{ $jenis === 'umkm' ? ($tarik->umkm?->nama ?? '—') : ($tarik->user?->name ?? '—') }}
                            </p>
                            @if ($jenis !== 'umkm' && $tarik->user?->email)
                                <p class="text-xs text-gray-500">{{ $tarik->user->email }}</p>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 font-semibold text-primary-900">
                            {{ Rupiah::format($tarik->jumlah) }}
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            <p>{{ $tarik->nama_bank ?? '—' }}</p>
                            <p class="font-mono text-xs">{{ $tarik->no_rekening }}</p>
                            <p class="text-xs text-gray-500">a.n. {{ $tarik->atas_nama }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <x-ui.lencana :status="$tarik->status"/>
                            @if ($tarik->catatan)
                                <p class="mt-1 max-w-xs text-xs text-gray-500">{{ $tarik->catatan }}</p>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-xs text-gray-500">
                            {{ $tarik->created_at->translatedFormat('j M Y') }}
                            @if ($tarik->penyetuju)
                                <span class="block text-gray-400">oleh {{ $tarik->penyetuju->name }}</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            @if ($tarik->status === StatusPenarikan::Menunggu)
                                <div class="flex justify-end gap-1">
                                    <x-ui.tombol ukuran="kecil" wire:click="setujui({{ $tarik->id }})"
                                                 wire:confirm="Setujui penarikan {{ Rupiah::format($tarik->jumlah) }}?">
                                        Setujui
                                    </x-ui.tombol>
                                    <x-ui.tombol jenis="bahaya" ukuran="kecil"
                                                 wire:click="bukaFormTolak({{ $tarik->id }})">
                                        Tolak
                                    </x-ui.tombol>
                                </div>
                            @elseif ($jenis !== 'umkm' && $tarik->status === StatusPenarikan::Disetujui)
                                <x-ui.tombol jenis="kedua" ukuran="kecil"
                                             wire:click="tandaiSelesai({{ $tarik->id }})"
                                             wire:confirm="Tandai penarikan ini sudah ditransfer?">
                                    Tandai selesai
                                </x-ui.tombol>
                            @endif
                        </td>
                    </tr>

                    @if ($tolakId === $tarik->id)
                        <tr class="bg-red-50">
                            <td colspan="6" class="px-4 py-4">
                                <form wire:submit="tolak" class="space-y-3">
                                    <x-ui.bidang label="Alasan penolakan" untuk="tolak-tarik-{{ $tarik->id }}"
                                                 :wajib="true"
                                                 petunjuk="Pemohon membaca alasan ini. Saldonya akan dikembalikan."
                                                 :galat="$errors->first('alasanTolak')">
                                        <textarea id="tolak-tarik-{{ $tarik->id }}" wire:model="alasanTolak" rows="2"
                                                  class="block w-full rounded-xl border border-red-200 bg-white px-3.5
                                                         py-2.5 text-sm focus:border-red-400 focus:outline-none
                                                         focus:ring-4 focus:ring-red-100"></textarea>
                                    </x-ui.bidang>

                                    <div class="flex gap-2">
                                        <x-ui.tombol tipe="submit" jenis="bahaya" ukuran="kecil">
                                            Tolak dan kembalikan saldo
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
        </x-ui.kartu>
    @endif
</div>
