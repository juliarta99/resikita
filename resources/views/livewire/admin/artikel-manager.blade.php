@php use App\Enums\StatusArtikel; @endphp

<div>
    <x-ui.kepala-halaman
        judul="Manajemen artikel"
        keterangan="Konten literasi lingkungan. Versi siap dibacakan disiapkan otomatis saat artikel disimpan.">
        <x-slot:aksi>
            <x-ui.tombol wire:click="bukaForm" ikon="plus">Tulis artikel</x-ui.tombol>
        </x-slot:aksi>
    </x-ui.kepala-halaman>

    <div class="mb-6 grid gap-3 sm:grid-cols-2 lg:max-w-2xl">
        <x-ui.bidang label="Cari" untuk="cari-artikel">
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                    <x-ui.ikon nama="cari" class="h-4 w-4"/>
                </span>
                <x-ui.isian id="cari-artikel" wire:model.live.debounce.400ms="cari" class="pl-9"
                            placeholder="Cari judul artikel"/>
            </div>
        </x-ui.bidang>

        <x-ui.bidang label="Status" untuk="status-artikel-filter">
            <x-ui.pilihan id="status-artikel-filter" wire:model.live="status" kosong="Semua status"
                          :opsi="$statusTersedia"/>
        </x-ui.bidang>
    </div>

    <x-ui.kartu padat>
        @if ($daftar->isEmpty())
            <x-ui.kosong ikon="buku" judul="Belum ada artikel"
                         pesan="Tulis artikel pertama untuk mengisi menu literasi di aplikasi warga.">
                <x-slot:aksi>
                    <x-ui.tombol wire:click="bukaForm" ikon="plus">Tulis artikel</x-ui.tombol>
                </x-slot:aksi>
            </x-ui.kosong>
        @else
            <x-ui.tabel :kepala="['Judul', 'Kategori', 'Status', 'Dibaca', 'Didengarkan', '']">
                @foreach ($daftar as $artikel)
                    <tr>
                        <td class="px-4 py-3">
                            <p class="font-medium text-primary-900">{{ $artikel->judul }}</p>
                            <p class="text-xs text-gray-500">
                                {{ $artikel->tipe->label() }}
                                &middot; {{ $artikel->estimasi_baca_menit ?? '—' }} menit baca
                                &middot; oleh {{ $artikel->penulis?->name ?? '—' }}
                            </p>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $artikel->kategori?->nama ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <x-ui.lencana :status="$artikel->status"/>
                            @if ($artikel->is_unggulan)
                                <x-ui.lencana class="ml-1" warna="kuning" label="Unggulan"/>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ number_format($artikel->dilihat) }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ number_format($artikel->didengarkan) }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            <div class="flex justify-end gap-1">
                                <button type="button" wire:click="bukaForm({{ $artikel->id }})"
                                        class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-primary-900"
                                        aria-label="Sunting artikel {{ $artikel->judul }}">
                                    <x-ui.ikon nama="pensil" class="h-4 w-4"/>
                                </button>

                                <button type="button" wire:click="ubahStatus({{ $artikel->id }})"
                                        class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100"
                                        aria-label="{{ $artikel->status === StatusArtikel::Published ? 'Kembalikan ke draf' : 'Terbitkan' }} artikel {{ $artikel->judul }}">
                                    <x-ui.ikon :nama="$artikel->status === StatusArtikel::Published ? 'mata-tutup' : 'mata'"
                                               class="h-4 w-4"/>
                                </button>

                                <button type="button" wire:click="hapus({{ $artikel->id }})"
                                        wire:confirm="Hapus artikel {{ $artikel->judul }}? Tindakan ini tidak dapat dibatalkan."
                                        class="rounded-lg p-2 text-red-500 transition hover:bg-red-50"
                                        aria-label="Hapus artikel {{ $artikel->judul }}">
                                    <x-ui.ikon nama="sampah" class="h-4 w-4"/>
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-ui.tabel>

            <div class="border-t border-gray-100 px-5 py-3">{{ $daftar->links() }}</div>
        @endif
    </x-ui.kartu>

    <x-modal active="formTerbuka" maxWidth="max-w-3xl">
        <form wire:submit="simpan">
            <div class="border-b border-gray-100 px-6 py-4">
                <h2 class="text-base font-semibold text-primary-900">
                    {{ $artikelId ? 'Sunting artikel' : 'Tulis artikel' }}
                </h2>
            </div>

            <div class="max-h-[65vh] space-y-4 overflow-y-auto px-6 py-5">
                <x-ui.bidang label="Judul" untuk="judul-artikel" :wajib="true" :galat="$errors->first('judul')">
                    <x-ui.isian id="judul-artikel" wire:model="judul" :galat="$errors->has('judul')"/>
                </x-ui.bidang>

                <div class="grid gap-4 sm:grid-cols-3">
                    <x-ui.bidang label="Kategori" untuk="kategori-artikel" :galat="$errors->first('kategoriId')">
                        <x-ui.pilihan id="kategori-artikel" wire:model="kategoriId" kosong="Tanpa kategori"
                                      :opsi="$kategoriTersedia->all()"/>
                    </x-ui.bidang>

                    <x-ui.bidang label="Tipe" untuk="tipe-artikel" :wajib="true" :galat="$errors->first('tipe')">
                        <x-ui.pilihan id="tipe-artikel" wire:model="tipe" :opsi="$tipeTersedia"/>
                    </x-ui.bidang>

                    <x-ui.bidang label="Status" untuk="status-artikel" :wajib="true"
                                 :galat="$errors->first('statusArtikel')">
                        <x-ui.pilihan id="status-artikel" wire:model="statusArtikel" :opsi="$statusTersedia"/>
                    </x-ui.bidang>
                </div>

                <x-ui.bidang label="Isi artikel" untuk="konten-artikel" :wajib="true"
                             petunjuk="Markdown. Judul, tabel, dan blok kode dibuang otomatis dari versi yang dibacakan."
                             :galat="$errors->first('konten')">
                    <textarea id="konten-artikel" wire:model="konten" rows="14"
                              class="block w-full rounded-xl border px-3.5 py-2.5 font-mono text-sm shadow-sm
                                     focus:outline-none focus:ring-4
                                     {{ $errors->has('konten')
                                         ? 'border-red-300 focus:border-red-500 focus:ring-red-200'
                                         : 'border-gray-300 focus:border-primary-500 focus:ring-primary-100' }}"></textarea>
                </x-ui.bidang>

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.bidang label="Tautan video" untuk="video-artikel"
                                 petunjuk="Opsional. Ditempel di akhir artikel."
                                 :galat="$errors->first('videoUrl')">
                        <x-ui.isian id="video-artikel" tipe="url" wire:model="videoUrl"
                                    placeholder="https://youtube.com/…" :galat="$errors->has('videoUrl')"/>
                    </x-ui.bidang>

                    <x-ui.bidang label="Gambar sampul" untuk="thumbnail-artikel"
                                 petunjuk="JPG, PNG, atau WebP. Maksimal 2 MB."
                                 :galat="$errors->first('thumbnailBaru')">
                        <input id="thumbnail-artikel" type="file" wire:model="thumbnailBaru" accept="image/*"
                               class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0
                                      file:bg-primary-50 file:px-3 file:py-2 file:text-sm file:font-medium
                                      file:text-primary-700 hover:file:bg-primary-100">
                        <div wire:loading wire:target="thumbnailBaru" class="text-xs text-gray-500">Mengunggah…</div>
                    </x-ui.bidang>
                </div>

                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" wire:model="isUnggulan"
                           class="h-4 w-4 rounded border-gray-300 text-primary-500 focus:ring-primary-200">
                    Tandai sebagai artikel unggulan
                </label>
            </div>

            <div class="flex justify-end gap-2 border-t border-gray-100 px-6 py-4">
                <x-ui.tombol jenis="kedua" wire:click="tutupForm">Batal</x-ui.tombol>
                <x-ui.tombol tipe="submit" wire:loading.attr="disabled" wire:target="simpan,thumbnailBaru">
                    {{ $artikelId ? 'Simpan perubahan' : 'Simpan artikel' }}
                </x-ui.tombol>
            </div>
        </form>
    </x-modal>
</div>
