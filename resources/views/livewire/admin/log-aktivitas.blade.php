<div>
    <x-ui.kepala-halaman
        judul="Log aktivitas"
        keterangan="Jejak tindakan pengguna. Baca saja, catatan yang bisa diubah berhenti menjadi bukti."/>

    <div class="mb-5 grid gap-3 sm:grid-cols-2 lg:max-w-2xl">
        <x-ui.bidang label="Cari" untuk="cari-log">
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                    <x-ui.ikon nama="cari" class="h-4 w-4"/>
                </span>
                <x-ui.isian id="cari-log" wire:model.live.debounce.400ms="cari" class="pl-9"
                            placeholder="Pengguna, keterangan, atau alamat IP"/>
            </div>
        </x-ui.bidang>

        <x-ui.bidang label="Jenis aksi" untuk="aksi-log">
            <x-ui.pilihan id="aksi-log" wire:model.live="aksi" kosong="Semua aksi"
                          :opsi="$aksiTersedia->all()"/>
        </x-ui.bidang>
    </div>

    <x-ui.kartu padat>
        @if ($daftar->isEmpty())
            <x-ui.kosong ikon="jejak" judul="Belum ada catatan"
                         pesan="Log terisi sendiri seiring pengguna melakukan tindakan yang tercatat."/>
        @else
            <x-ui.tabel :kepala="['Waktu', 'Pengguna', 'Aksi', 'Keterangan', 'Asal']">
                @foreach ($daftar as $log)
                    <tr>
                        <td class="whitespace-nowrap px-4 py-3 text-xs text-gray-500">
                            {{ $log->created_at->translatedFormat('j M Y, H:i:s') }}
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-primary-900">{{ $log->user?->name ?? 'Sistem' }}</p>
                            @if ($log->user?->email)
                                <p class="text-xs text-gray-500">{{ $log->user->email }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <x-ui.lencana warna="abu" :label="$log->aksi"/>
                        </td>
                        <td class="max-w-md px-4 py-3 text-gray-600">{{ $log->deskripsi ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <p class="font-mono text-xs text-gray-600">{{ $log->ip_address ?? '—' }}</p>
                            @if ($log->user_agent)
                                <p class="max-w-xs truncate text-xs text-gray-400" title="{{ $log->user_agent }}">
                                    {{ $log->user_agent }}
                                </p>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-ui.tabel>

            <div class="border-t border-gray-100 px-5 py-3">{{ $daftar->links() }}</div>
        @endif
    </x-ui.kartu>
</div>
