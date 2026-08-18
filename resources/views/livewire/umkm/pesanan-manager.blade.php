@php use App\Support\Rupiah; use App\Enums\StatusPesanan; @endphp

<div>
    <x-ui.kepala-halaman
        judul="Pesanan"
        keterangan="Pesanan yang masuk ke toko Anda. Satu pesanan selalu berisi produk dari satu toko saja."/>

    @if ($daftar === null)
        <x-ui.kartu>
            <x-ui.kosong ikon="peringatan" judul="Akun belum terhubung ke UMKM"
                         pesan="Hubungi admin Resikita untuk menghubungkan akun Anda."/>
        </x-ui.kartu>
    @else
        <div class="mb-5 grid gap-3 sm:grid-cols-2 lg:max-w-2xl">
            <x-ui.bidang label="Cari" untuk="cari-pesanan">
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <x-ui.ikon nama="cari" class="h-4 w-4"/>
                    </span>
                    <x-ui.isian id="cari-pesanan" wire:model.live.debounce.400ms="cari" class="pl-9"
                                placeholder="Kode pesanan, penerima, atau resi"/>
                </div>
            </x-ui.bidang>

            <x-ui.bidang label="Status" untuk="status-pesanan">
                <x-ui.pilihan id="status-pesanan" wire:model.live="status" kosong="Semua status"
                              :opsi="$statusTersedia"/>
            </x-ui.bidang>
        </div>

        @if ($daftar->isEmpty())
            <x-ui.kartu>
                <x-ui.kosong ikon="keranjang" judul="Belum ada pesanan"
                             pesan="Pesanan yang masuk akan tampil di sini beserta alamat pengirimannya."/>
            </x-ui.kartu>
        @else
            <div class="space-y-4">
                @foreach ($daftar as $pesanan)
                    <x-ui.kartu>
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-mono text-xs text-gray-500">{{ $pesanan->kode }}</p>
                                <p class="mt-0.5 text-sm font-semibold text-primary-900">
                                    {{ $pesanan->nama_penerima ?? $pesanan->user?->name ?? 'Pembeli' }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ $pesanan->created_at->translatedFormat('j F Y, H:i') }}
                                </p>
                            </div>

                            <div class="text-right">
                                <x-ui.lencana :status="$pesanan->status"/>
                                <p class="mt-1.5 text-lg font-bold text-primary-900">
                                    {{ Rupiah::format($pesanan->total) }}
                                </p>
                            </div>
                        </div>

                        <ul class="mt-4 space-y-1.5 border-t border-gray-100 pt-4 text-sm">
                            @foreach ($pesanan->item as $item)
                                <li class="flex justify-between gap-3">
                                    <span class="min-w-0 truncate text-gray-700">
                                        {{ $item->nama_snapshot }}
                                        <span class="text-gray-400">&times;{{ $item->qty }}</span>
                                    </span>
                                    <span class="flex-none text-gray-600">{{ Rupiah::format($item->subtotal) }}</span>
                                </li>
                            @endforeach
                        </ul>

                        <dl class="mt-4 grid gap-3 border-t border-gray-100 pt-4 text-sm sm:grid-cols-2">
                            <div>
                                <dt class="text-xs text-gray-500">Alamat kirim</dt>
                                <dd class="mt-0.5 text-gray-700">{{ $pesanan->alamat_kirim }}</dd>
                                @if ($pesanan->phone_penerima)
                                    <dd class="text-xs text-gray-500">{{ $pesanan->phone_penerima }}</dd>
                                @endif
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500">Pengiriman</dt>
                                <dd class="mt-0.5 text-gray-700">
                                    {{ trim(($pesanan->kurir ?? '—').' '.($pesanan->layanan_kurir ?? '')) }}
                                    &middot; ongkir {{ Rupiah::format($pesanan->ongkir) }}
                                </dd>
                                @if ($pesanan->no_resi)
                                    <dd class="font-mono text-xs text-primary-700">Resi {{ $pesanan->no_resi }}</dd>
                                @endif
                            </div>
                        </dl>

                        @if ($pesanan->status === StatusPesanan::Dibayar || $pesanan->status === StatusPesanan::Dikemas)
                            <div class="mt-4 flex flex-wrap gap-2 border-t border-gray-100 pt-4">
                                @if ($pesanan->status === StatusPesanan::Dibayar)
                                    <x-ui.tombol ukuran="kecil" wire:click="tandaiDikemas({{ $pesanan->id }})"
                                                 ikon="kotak">
                                        Tandai sedang dikemas
                                    </x-ui.tombol>
                                @endif

                                <x-ui.tombol jenis="kedua" ukuran="kecil" wire:click="bukaFormResi({{ $pesanan->id }})">
                                    Masukkan nomor resi
                                </x-ui.tombol>
                            </div>
                        @endif

                        @if ($resiUntuk === $pesanan->id)
                            <form wire:submit="kirim" class="mt-4 space-y-3 rounded-xl bg-gray-50 p-4">
                                <x-ui.bidang label="Nomor resi" untuk="resi-{{ $pesanan->id }}" :wajib="true"
                                             :petunjuk="$kurirDipakai !== '' ? 'Kurir dipilih pembeli: '.$kurirDipakai : null"
                                             :galat="$errors->first('noResi')">
                                    <x-ui.isian id="resi-{{ $pesanan->id }}" wire:model="noResi"
                                                class="font-mono" :galat="$errors->has('noResi')"/>
                                </x-ui.bidang>

                                <div class="flex gap-2">
                                    <x-ui.tombol tipe="submit" ukuran="kecil">Simpan dan tandai dikirim</x-ui.tombol>
                                    <x-ui.tombol jenis="polos" ukuran="kecil" wire:click="$set('resiUntuk', null)">
                                        Batal
                                    </x-ui.tombol>
                                </div>
                            </form>
                        @endif
                    </x-ui.kartu>
                @endforeach
            </div>

            <div class="mt-6">{{ $daftar->links() }}</div>
        @endif
    @endif
</div>
