<div>
    <x-ui.kepala-halaman
        judul="Master data"
        keterangan="Daftar kategori yang dipakai di seluruh sistem.">
        <x-slot:aksi>
            <x-ui.tombol wire:click="bukaForm" ikon="plus">Tambah kategori</x-ui.tombol>
        </x-slot:aksi>
    </x-ui.kepala-halaman>

    <div class="mb-6 inline-flex rounded-xl border border-gray-300 bg-white p-1" role="tablist"
         aria-label="Jenis master data">
        @foreach (['laporan' => 'Kategori laporan', 'artikel' => 'Kategori artikel', 'produk' => 'Kategori produk'] as $nilai => $teks)
            <button type="button" role="tab" wire:click="gantiTab('{{ $nilai }}')"
                    @if ($tab === $nilai) aria-selected="true" @else aria-selected="false" @endif
                    class="rounded-lg px-3.5 py-1.5 text-sm font-medium transition
                           {{ $tab === $nilai ? 'bg-primary-500 text-white' : 'text-gray-600 hover:bg-gray-50' }}">
                {{ $teks }}
            </button>
        @endforeach
    </div>

    <x-ui.kartu padat>
        @if ($daftar->isEmpty())
            <x-ui.kosong ikon="kotak" judul="Belum ada kategori"
                         pesan="Tambahkan kategori pertama supaya warga punya pilihan saat mengisi formulir.">
                <x-slot:aksi>
                    <x-ui.tombol wire:click="bukaForm" ikon="plus">Tambah kategori</x-ui.tombol>
                </x-slot:aksi>
            </x-ui.kosong>
        @else
            <x-ui.tabel :kepala="['Nama', 'Keterangan', 'Dipakai', 'Status', '']">
                @foreach ($daftar as $item)
                    <tr>
                        <td class="px-4 py-3 font-medium text-primary-900">{{ $item->nama }}</td>
                        <td class="max-w-md px-4 py-3 text-gray-600">
                            @if ($tab === 'laporan')
                                {{ $item->deskripsi ?? '—' }}
                            @else
                                <span class="font-mono text-xs text-gray-400">{{ $item->slug }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            {{ number_format($item->laporan_count ?? $item->artikel_count ?? $item->produk_count ?? 0) }}
                        </td>
                        <td class="px-4 py-3">
                            @if ($tab === 'laporan')
                                <x-ui.lencana :warna="$item->is_active ? 'hijau' : 'abu'"
                                              :label="$item->is_active ? 'Aktif' : 'Nonaktif'"/>
                            @else
                                <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            <div class="flex justify-end gap-1">
                                <button type="button" wire:click="bukaForm({{ $item->id }})"
                                        class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-primary-900"
                                        aria-label="Sunting kategori {{ $item->nama }}">
                                    <x-ui.ikon nama="pensil" class="h-4 w-4"/>
                                </button>

                                @if ($tab === 'laporan')
                                    <button type="button" wire:click="ubahAktif({{ $item->id }})"
                                            class="rounded-lg p-2 transition hover:bg-gray-100
                                                   {{ $item->is_active ? 'text-gray-500' : 'text-primary-600' }}"
                                            aria-label="{{ $item->is_active ? 'Nonaktifkan' : 'Aktifkan' }} kategori {{ $item->nama }}">
                                        <x-ui.ikon :nama="$item->is_active ? 'silang' : 'centang'" class="h-4 w-4"/>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-ui.tabel>
        @endif
    </x-ui.kartu>

    <x-modal active="formTerbuka">
        <form wire:submit="simpan">
            <div class="border-b border-gray-100 px-6 py-4">
                <h2 class="text-base font-semibold text-primary-900">
                    {{ $itemId ? 'Sunting kategori' : 'Tambah kategori' }}
                </h2>
            </div>

            <div class="space-y-4 px-6 py-5">
                <x-ui.bidang label="Nama" untuk="nama-kategori" :wajib="true" :galat="$errors->first('nama')">
                    <x-ui.isian id="nama-kategori" wire:model="nama" :galat="$errors->has('nama')"/>
                </x-ui.bidang>

                @if ($tab === 'laporan')
                    <x-ui.bidang label="Keterangan" untuk="deskripsi-kategori"
                                 petunjuk="Membantu warga memilih kategori yang tepat."
                                 :galat="$errors->first('deskripsi')">
                        <textarea id="deskripsi-kategori" wire:model="deskripsi" rows="2"
                                  class="block w-full rounded-xl border border-gray-300 px-3.5 py-2.5 text-sm
                                         shadow-sm focus:border-primary-500 focus:outline-none focus:ring-4
                                         focus:ring-primary-100"></textarea>
                    </x-ui.bidang>
                @endif

                @if ($tab !== 'artikel')
                    <x-ui.bidang label="Nama ikon" untuk="ikon-kategori"
                                 petunjuk="Opsional. Dipakai aplikasi ponsel untuk memilih gambar."
                                 :galat="$errors->first('ikon')">
                        <x-ui.isian id="ikon-kategori" wire:model="ikon" placeholder="sampah"/>
                    </x-ui.bidang>
                @endif

                @if ($tab === 'laporan')
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" wire:model="isActive"
                               class="h-4 w-4 rounded border-gray-300 text-primary-500 focus:ring-primary-200">
                        Bisa dipilih warga saat melapor
                    </label>
                @endif
            </div>

            <div class="flex justify-end gap-2 border-t border-gray-100 px-6 py-4">
                <x-ui.tombol jenis="kedua" wire:click="tutupForm">Batal</x-ui.tombol>
                <x-ui.tombol tipe="submit">{{ $itemId ? 'Simpan perubahan' : 'Tambah' }}</x-ui.tombol>
            </div>
        </form>
    </x-modal>
</div>
