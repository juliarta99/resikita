<div>
    <x-ui.kepala-halaman
        judul="Manajemen pengguna"
        keterangan="Seluruh akun Resikita. Identitas utama adalah email, tidak ada data kependudukan yang disimpan."/>

    <x-ui.kartu padat class="mb-6">
        <div class="grid gap-3 p-5 sm:grid-cols-3">
            <x-ui.bidang label="Cari" untuk="cari-pengguna">
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <x-ui.ikon nama="cari" class="h-4 w-4"/>
                    </span>
                    <x-ui.isian id="cari-pengguna" wire:model.live.debounce.400ms="cari" class="pl-9"
                                placeholder="Nama, email, atau telepon"/>
                </div>
            </x-ui.bidang>

            <x-ui.bidang label="Peran" untuk="peran-pengguna">
                <x-ui.pilihan id="peran-pengguna" wire:model.live="peran" kosong="Semua peran"
                              :opsi="$peranTersedia"/>
            </x-ui.bidang>

            <x-ui.bidang label="Penyaring">
                <label class="flex h-[42px] items-center gap-2 rounded-xl border border-gray-300 px-3.5 text-sm text-gray-700">
                    <input type="checkbox" wire:model.live="hanyaNonaktif"
                           class="h-4 w-4 rounded border-gray-300 text-primary-500 focus:ring-primary-200">
                    Hanya akun nonaktif
                </label>
            </x-ui.bidang>
        </div>
    </x-ui.kartu>

    <x-ui.kartu padat>
        @if ($daftar->isEmpty())
            <x-ui.kosong ikon="orang" judul="Tidak ada pengguna"
                         pesan="Tidak ada akun yang cocok dengan penyaring ini."/>
        @else
            <x-ui.tabel :kepala="['Pengguna', 'Peran', 'Keterkaitan', 'Status', 'Bergabung', '']">
                @foreach ($daftar as $pengguna)
                    <tr class="{{ $pengguna->is_active ? '' : 'opacity-60' }}">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                @if ($pengguna->urlAvatar())
                                    <img src="{{ $pengguna->urlAvatar() }}" alt=""
                                         class="h-9 w-9 flex-none rounded-xl object-cover">
                                @else
                                    <span class="flex h-9 w-9 flex-none items-center justify-center rounded-xl
                                                 bg-gray-100 text-xs font-semibold text-gray-500" aria-hidden="true">
                                        {{ $pengguna->inisial() }}
                                    </span>
                                @endif
                                <div class="min-w-0">
                                    <p class="truncate font-medium text-primary-900">{{ $pengguna->name }}</p>
                                    <p class="truncate text-xs text-gray-500">{{ $pengguna->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <x-ui.lencana warna="hijau"
                                          :label="$pengguna->roleUtama()?->label() ?? 'Tanpa peran'"/>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-600">
                            @if ($pengguna->wilayah)
                                <p>{{ $pengguna->wilayah->namaLengkap() }}</p>
                            @endif
                            @if ($pengguna->bankSampah)
                                <p>{{ $pengguna->bankSampah->nama }}</p>
                            @endif
                            @if ($pengguna->umkm)
                                <p>{{ $pengguna->umkm->nama }}</p>
                            @endif
                            @if (! $pengguna->wilayah && ! $pengguna->bankSampah && ! $pengguna->umkm)
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <x-ui.lencana :warna="$pengguna->is_active ? 'hijau' : 'abu'"
                                          :label="$pengguna->is_active ? 'Aktif' : 'Nonaktif'"/>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-xs text-gray-500">
                            {{ $pengguna->created_at->translatedFormat('j M Y') }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            <button type="button" wire:click="ubahStatus({{ $pengguna->id }})"
                                    wire:confirm="{{ $pengguna->is_active
                                        ? 'Nonaktifkan akun '.$pengguna->name.'? Sesi aplikasinya langsung diputus.'
                                        : 'Aktifkan kembali akun '.$pengguna->name.'?' }}"
                                    class="rounded-lg p-2 transition hover:bg-gray-100
                                           {{ $pengguna->is_active ? 'text-red-500 hover:text-red-700' : 'text-primary-600' }}"
                                    aria-label="{{ $pengguna->is_active ? 'Nonaktifkan' : 'Aktifkan' }} akun {{ $pengguna->name }}">
                                <x-ui.ikon :nama="$pengguna->is_active ? 'silang' : 'centang'" class="h-4 w-4"/>
                            </button>
                        </td>
                    </tr>
                @endforeach
            </x-ui.tabel>

            <div class="border-t border-gray-100 px-5 py-3">{{ $daftar->links() }}</div>
        @endif
    </x-ui.kartu>
</div>
