@php use App\Support\Rupiah; use App\Enums\TipeTransaksiDompet; @endphp

<div>
    <x-ui.kepala-halaman
        judul="Saldo UMKM"
        keterangan="Pendapatan penjualan dan riwayat penarikannya.">
        @if ($umkm !== null)
            <x-slot:aksi>
                <x-ui.tombol wire:click="bukaForm" ikon="dompet">Ajukan penarikan</x-ui.tombol>
            </x-slot:aksi>
        @endif
    </x-ui.kepala-halaman>

    @if ($umkm === null)
        <x-ui.kartu>
            <x-ui.kosong ikon="peringatan" judul="Akun belum terhubung ke UMKM"
                         pesan="Hubungi admin Resikita untuk menghubungkan akun Anda."/>
        </x-ui.kartu>
    @else
        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6">
                <x-ui.kartu>
                    <p class="text-xs font-medium text-gray-500">Saldo tersedia</p>
                    <p class="mt-1 text-3xl font-bold tracking-tight text-primary-900">
                        {{ Rupiah::format($saldo) }}
                    </p>
                    <p class="mt-3 text-xs text-gray-500">
                        Minimal penarikan {{ Rupiah::format($minimum) }}. Saldo langsung dipotong
                        saat pengajuan dibuat, bukan saat disetujui.
                    </p>
                </x-ui.kartu>

                <x-ui.kartu padat judul="Riwayat penarikan">
                    @if ($penarikan->isEmpty())
                        <x-ui.kosong ikon="dompet" judul="Belum ada penarikan"
                                     pesan="Pengajuan Anda akan tampil di sini beserta statusnya."/>
                    @else
                        <ul class="divide-y divide-gray-100">
                            @foreach ($penarikan as $tarik)
                                <li class="px-5 py-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-primary-900">
                                                {{ Rupiah::format($tarik->jumlah) }}
                                            </p>
                                            <p class="truncate text-xs text-gray-500">
                                                {{ $tarik->nama_bank }} &middot; {{ $tarik->no_rekening }}
                                            </p>
                                            <p class="text-xs text-gray-400">
                                                {{ $tarik->created_at->translatedFormat('j M Y') }}
                                            </p>
                                        </div>
                                        <x-ui.lencana :status="$tarik->status"/>
                                    </div>

                                    @if ($tarik->catatan)
                                        <p class="mt-2 rounded-lg bg-gray-50 p-2.5 text-xs text-gray-600">
                                            {{ $tarik->catatan }}
                                        </p>
                                    @endif
                                </li>
                            @endforeach
                        </ul>

                        <div class="border-t border-gray-100 px-5 py-3">{{ $penarikan->links() }}</div>
                    @endif
                </x-ui.kartu>
            </div>

            {{-- Mutasi --}}
            <x-ui.kartu padat class="lg:col-span-2" judul="Mutasi saldo"
                        keterangan="Setiap perubahan saldo tercatat dengan posisi sebelum dan sesudahnya.">
                @if ($mutasi->isEmpty())
                    <x-ui.kosong ikon="jejak" judul="Belum ada mutasi"
                                 pesan="Saldo bertambah otomatis setiap pesanan dinyatakan selesai."/>
                @else
                    <x-ui.tabel :kepala="['Keterangan', 'Jenis', 'Jumlah', 'Saldo akhir', 'Waktu']">
                        @foreach ($mutasi as $baris)
                            @php $masuk = ! in_array($baris->tipe, [TipeTransaksiDompet::Penarikan, TipeTransaksiDompet::Belanja], true); @endphp
                            <tr>
                                <td class="px-4 py-3 text-gray-700">{{ $baris->keterangan ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <x-ui.lencana :warna="$masuk ? 'hijau' : 'abu'" :label="$baris->tipe->label()"/>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 font-medium {{ $masuk ? 'text-primary-700' : 'text-red-600' }}">
                                    {{ $masuk ? '+' : '−' }}{{ Rupiah::format($baris->jumlah) }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-gray-600">
                                    {{ Rupiah::format($baris->saldo_sesudah) }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-xs text-gray-500">
                                    {{ $baris->created_at->translatedFormat('j M Y, H:i') }}
                                </td>
                            </tr>
                        @endforeach
                    </x-ui.tabel>

                    <div class="border-t border-gray-100 px-5 py-3">{{ $mutasi->links() }}</div>
                @endif
            </x-ui.kartu>
        </div>
    @endif

    <x-modal active="formTerbuka">
        <form wire:submit="ajukan">
            <div class="border-b border-gray-100 px-6 py-4">
                <h2 class="text-base font-semibold text-primary-900">Ajukan penarikan saldo</h2>
            </div>

            <div class="space-y-4 px-6 py-5">
                <x-ui.bidang label="Jumlah" untuk="jumlah-tarik" :wajib="true"
                             petunjuk="Rupiah penuh tanpa titik. Minimal {{ Rupiah::format($minimum ?? 0) }}."
                             :galat="$errors->first('jumlah')">
                    <x-ui.isian id="jumlah-tarik" wire:model="jumlah" inputmode="numeric" placeholder="100000"
                                :galat="$errors->has('jumlah')"/>
                </x-ui.bidang>

                <x-ui.bidang label="Nama bank" untuk="nama-bank" :wajib="true" :galat="$errors->first('namaBank')">
                    <x-ui.isian id="nama-bank" wire:model="namaBank" placeholder="BRI"
                                :galat="$errors->has('namaBank')"/>
                </x-ui.bidang>

                <x-ui.bidang label="Nomor rekening" untuk="no-rekening" :wajib="true"
                             :galat="$errors->first('noRekening')">
                    <x-ui.isian id="no-rekening" wire:model="noRekening" inputmode="numeric"
                                class="font-mono" :galat="$errors->has('noRekening')"/>
                </x-ui.bidang>

                <x-ui.bidang label="Atas nama" untuk="atas-nama" :wajib="true"
                             petunjuk="Harus sama persis dengan nama di rekening."
                             :galat="$errors->first('atasNama')">
                    <x-ui.isian id="atas-nama" wire:model="atasNama" :galat="$errors->has('atasNama')"/>
                </x-ui.bidang>
            </div>

            <div class="flex justify-end gap-2 border-t border-gray-100 px-6 py-4">
                <x-ui.tombol jenis="kedua" wire:click="$set('formTerbuka', false)">Batal</x-ui.tombol>
                <x-ui.tombol tipe="submit" wire:loading.attr="disabled" wire:target="ajukan">
                    Ajukan penarikan
                </x-ui.tombol>
            </div>
        </form>
    </x-modal>
</div>
