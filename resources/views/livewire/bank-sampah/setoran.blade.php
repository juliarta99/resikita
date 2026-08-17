@php use App\Support\Rupiah; @endphp

<div>
    <x-ui.kepala-halaman
        judul="Catat setoran"
        keterangan="Kenali nasabah lewat kode QR, timbang jenis per jenis, lalu tutup transaksinya sekali di akhir."/>

    @if ($bankSampah === null)
        <x-ui.kartu>
            <x-ui.kosong ikon="peringatan" judul="Akun belum terhubung ke unit bank sampah"
                         pesan="Hubungi admin Resikita untuk menghubungkan akun Anda."/>
        </x-ui.kartu>

    @elseif ($setoran === null)
        {{-- Langkah 1: kenali nasabah --}}
        <div class="mx-auto max-w-xl">
            <x-ui.kartu judul="Langkah 1, kenali nasabah"
                        keterangan="Pindai kode QR di aplikasi nasabah, atau ketik kodenya secara manual.">
                <form wire:submit="cariNasabah" class="space-y-4">
                    <x-ui.bidang label="Kode QR nasabah" untuk="kode-qr" :wajib="true"
                                 petunjuk="26 karakter. Kode ini berisi ULID acak, bukan data kependudukan."
                                 :galat="$errors->first('kodeQr')">
                        <x-ui.isian id="kode-qr" wire:model="kodeQr" autofocus
                                    class="font-mono uppercase tracking-wider"
                                    placeholder="01JQ0X8Z9K3M4N5P6Q7R8S9T0U"
                                    :galat="$errors->has('kodeQr')"/>
                    </x-ui.bidang>

                    <x-ui.tombol tipe="submit" class="w-full" ikon="cari"
                                 wire:loading.attr="disabled" wire:target="cariNasabah">
                        <span wire:loading.remove wire:target="cariNasabah">Buka setoran</span>
                        <span wire:loading wire:target="cariNasabah">Mencari…</span>
                    </x-ui.tombol>
                </form>
            </x-ui.kartu>

            @if ($katalog->isEmpty())
                <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                    <p class="text-sm text-amber-900">
                        Katalog harga Anda masih kosong, jadi setoran belum bisa ditimbang.
                        <a href="{{ route('bank-sampah.harga') }}" wire:navigate class="font-medium underline">
                            Isi katalog harga lebih dulu.
                        </a>
                    </p>
                </div>
            @endif
        </div>

    @else
        {{-- Langkah 2 dan 3 --}}
        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <x-ui.kartu>
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-medium text-gray-500">Nasabah</p>
                            <p class="mt-0.5 text-lg font-bold text-primary-900">
                                {{ $setoran->nasabah?->name ?? 'Nasabah terhapus' }}
                            </p>
                            <p class="font-mono text-xs text-gray-500">{{ $setoran->kode_setoran }}</p>
                        </div>
                        <x-ui.lencana :status="$setoran->status"/>
                    </div>
                </x-ui.kartu>

                <x-ui.kartu judul="Langkah 2, timbang sampah"
                            keterangan="Tambahkan satu baris untuk setiap jenis. Harga diambil dari katalog Anda saat ini dan dikunci pada baris itu.">
                    <form wire:submit="tambahItem" class="grid gap-3 sm:grid-cols-[1fr_auto_auto] sm:items-end">
                        <x-ui.bidang label="Jenis sampah" untuk="jenis" :galat="$errors->first('hargaId')">
                            <x-ui.pilihan id="jenis" wire:model="hargaId" kosong="Pilih jenis"
                                          :galat="$errors->has('hargaId')">
                                @foreach ($katalog as $harga)
                                    <option value="{{ $harga->id }}">
                                        {{ $harga->jenis_sampah }}, {{ Rupiah::format($harga->harga_per_satuan) }}/{{ $harga->satuan }}
                                    </option>
                                @endforeach
                            </x-ui.pilihan>
                        </x-ui.bidang>

                        <x-ui.bidang label="Berat (kg)" untuk="berat" :galat="$errors->first('berat')"
                                     class="sm:w-32">
                            <x-ui.isian id="berat" wire:model="berat" inputmode="decimal" placeholder="0,00"
                                        :galat="$errors->has('berat')"/>
                        </x-ui.bidang>

                        <x-ui.tombol tipe="submit" ikon="plus" class="h-[42px]">Tambah</x-ui.tombol>
                    </form>

                    <div class="mt-5 border-t border-gray-100 pt-5">
                        @if ($setoran->item->isEmpty())
                            <x-ui.kosong ikon="timbangan" judul="Belum ada yang ditimbang"
                                         pesan="Tambahkan minimal satu jenis sampah sebelum menutup transaksi."/>
                        @else
                            <x-ui.tabel :kepala="['Jenis', 'Berat', 'Harga/kg', 'Subtotal', '']">
                                @foreach ($setoran->item as $item)
                                    <tr>
                                        <td class="px-4 py-3 font-medium text-primary-900">{{ $item->jenis_snapshot }}</td>
                                        <td class="px-4 py-3 text-gray-600">
                                            {{ number_format((float) $item->berat, 2, ',', '.') }} kg
                                        </td>
                                        <td class="px-4 py-3 text-gray-600">{{ Rupiah::format($item->harga_snapshot) }}</td>
                                        <td class="px-4 py-3 font-medium text-primary-900">
                                            {{ Rupiah::format($item->subtotal) }}
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <button type="button" wire:click="hapusItem({{ $item->id }})"
                                                    class="rounded-lg p-1.5 text-red-500 transition hover:bg-red-50"
                                                    aria-label="Hapus timbangan {{ $item->jenis_snapshot }}">
                                                <x-ui.ikon nama="sampah" class="h-4 w-4"/>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </x-ui.tabel>
                        @endif
                    </div>
                </x-ui.kartu>
            </div>

            {{-- Ringkasan dan penutupan --}}
            <div class="space-y-6">
                <x-ui.kartu judul="Langkah 3, tutup transaksi">
                    <dl class="space-y-3 border-b border-gray-100 pb-4">
                        <div class="flex justify-between text-sm">
                            <dt class="text-gray-500">Jumlah jenis</dt>
                            <dd class="font-medium text-primary-900">{{ $setoran->item->count() }}</dd>
                        </div>
                        <div class="flex justify-between text-sm">
                            <dt class="text-gray-500">Total berat</dt>
                            <dd class="font-medium text-primary-900">
                                {{ number_format((float) $setoran->total_berat, 2, ',', '.') }} kg
                            </dd>
                        </div>
                        <div class="flex items-baseline justify-between">
                            <dt class="text-sm text-gray-500">Total nilai</dt>
                            <dd class="text-xl font-bold text-primary-900">
                                {{ Rupiah::format($setoran->total_nilai) }}
                            </dd>
                        </div>
                    </dl>

                    <div class="mt-4 space-y-3">
                        <x-ui.bidang label="Catatan" untuk="catatan-setoran"
                                     petunjuk="Opsional. Tersimpan bersama transaksi.">
                            <textarea id="catatan-setoran" wire:model="catatan" rows="2"
                                      class="block w-full rounded-xl border border-gray-300 px-3.5 py-2.5 text-sm
                                             shadow-sm focus:border-primary-500 focus:outline-none focus:ring-4
                                             focus:ring-primary-100"></textarea>
                        </x-ui.bidang>

                        <x-ui.tombol wire:click="selesaikan" class="w-full" ikon="centang"
                                     :disabled="$setoran->item->isEmpty()"
                                     wire:loading.attr="disabled" wire:target="selesaikan">
                            Selesaikan dan tambah saldo
                        </x-ui.tombol>

                        <x-ui.tombol jenis="kedua" class="w-full" wire:click="batalkan"
                                     wire:confirm="Batalkan setoran ini? Seluruh timbangan akan dibuang dan tidak ada saldo yang berpindah.">
                            Batalkan setoran
                        </x-ui.tombol>
                    </div>

                    <p class="mt-4 flex items-start gap-2 rounded-xl bg-gray-50 p-3 text-xs text-gray-600">
                        <x-ui.ikon nama="info" class="h-4 w-4 flex-none text-gray-400"/>
                        Saldo nasabah baru bertambah setelah transaksi ditutup, sekaligus untuk seluruh jenis.
                    </p>
                </x-ui.kartu>
            </div>
        </div>
    @endif
</div>
