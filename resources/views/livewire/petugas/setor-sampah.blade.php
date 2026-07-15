<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-primary-900">Setor Sampah</h1>
        <p class="mt-1 text-sm text-gray-500">Pindai QR nasabah atau masukkan kode/NIK, lalu catat setoran.</p>
    </div>
    @if (session('ok'))
        <div class="rounded-xl border border-primary-100 bg-primary-50 px-4 py-3 text-sm font-medium text-primary-700">{{ session('ok') }}</div>
    @endif
    @if (session('err'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{{ session('err') }}</div>
    @endif
    @if (! $nasabahId)
        {{-- Cari nasabah --}}
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6"
             x-data="{
                active: false,
                scanner: null,
                start() {
                    this.active = true;
                    this.$nextTick(() => {
                        this.scanner = new Html5Qrcode('qr-reader');
                        this.scanner.start(
                            { facingMode: 'environment' },
                            { fps: 10, qrbox: 220 },
                            (text) => { this.$wire.set('kode', text).then(() => this.$wire.cariNasabah()); this.stop(); },
                            () => {}
                        ).catch(() => { this.active = false; });
                    });
                },
                stop() {
                    if (this.scanner) {
                        this.scanner.stop().then(() => { this.scanner.clear(); this.scanner = null; }).catch(() => {});
                    }
                    this.active = false;
                }
             }">
            <input wire:model="kode" wire:keydown.enter="cariNasabah" placeholder="Kode QR atau NIK nasabah"
                   class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
            <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                <button wire:click="cariNasabah" class="rounded-lg bg-primary-500 px-5 py-2 text-sm font-semibold text-white hover:bg-primary-700 sm:flex-none">Cari</button>
                <button type="button" @click="active ? stop() : start()"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-primary-900 hover:bg-gray-50 sm:flex-none"
                        x-text="active ? 'Tutup Kamera' : 'Scan QR'"></button>
            </div>
            <div x-show="active" x-cloak class="mt-4">
                <div id="qr-reader" wire:ignore class="mx-auto max-w-sm overflow-hidden rounded-lg border border-gray-200"></div>
            </div>
            @error('nasabahId') <p class="mt-2 text-xs text-red-600">Pilih nasabah terlebih dahulu.</p> @enderror
        </div>
    @else
        {{-- Nasabah terpilih --}}
        <div class="flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-5">
            <div class="min-w-0">
                <p class="text-xs text-gray-400">Nasabah</p>
                <p class="truncate text-lg font-semibold text-primary-900">{{ $nasabahNama }}</p>
                <p class="text-xs text-gray-500">Kode: {{ $nasabahKode ?: '—' }} · Saldo: Rp {{ number_format($nasabahSaldo, 0, ',', '.') }}</p>
            </div>
            <button wire:click="gantiNasabah" class="w-full rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-primary-900 hover:bg-gray-50 sm:w-auto sm:flex-none">Ganti</button>
        </div>

        {{-- Rincian setoran --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 px-4 py-4 sm:px-6">
                <p class="text-base font-semibold text-primary-900">Rincian Setoran</p>
            </div>
            <div class="space-y-4 px-4 py-5 sm:space-y-3 sm:px-6">
                @foreach ($items as $i => $item)
                    {{-- Mobile: stack; Desktop: grid 12 --}}
                    <div wire:key="item-{{ $i }}" class="rounded-lg border border-gray-100 p-3 sm:grid sm:grid-cols-12 sm:items-start sm:gap-3 sm:rounded-none sm:border-0 sm:p-0">
                        <div class="sm:col-span-6">
                            <select wire:model.live="items.{{ $i }}.waste_price_id"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                                <option value="">— Jenis sampah —</option>
                                @foreach ($prices as $p)
                                    <option value="{{ $p->id }}">{{ $p->jenis_sampah }} (Rp {{ number_format($p->harga_per_kg, 0, ',', '.') }}/{{ $p->satuan }})</option>
                                @endforeach
                            </select>
                            @error('items.'.$i.'.waste_price_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="mt-2 sm:col-span-4 sm:mt-0">
                            <div class="flex items-center gap-2">
                                <input type="number" step="0.01" min="0" wire:model.live.debounce.400ms="items.{{ $i }}.berat"
                                       placeholder="Berat" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-primary-900 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                                <span class="text-sm text-gray-400">kg</span>
                            </div>
                            @error('items.'.$i.'.berat') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="mt-2 flex justify-end sm:col-span-2 sm:mt-0 sm:pt-2">
                            <button wire:click="hapusItem({{ $i }})" class="text-sm font-medium text-red-600 hover:text-red-700">Hapus</button>
                        </div>
                    </div>
                @endforeach
                <button wire:click="tambahItem" class="text-sm font-medium text-primary-500 hover:text-primary-700">+ Tambah jenis</button>
            </div>
            <div class="flex items-center justify-between border-t border-gray-200 bg-gray-50 px-4 py-4 sm:px-6">
                <div class="text-sm text-gray-500">
                    Total berat: <span class="font-medium text-primary-900">{{ number_format($totalBerat, 2, ',', '.') }} kg</span>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-400">Total nilai</p>
                    <p class="text-xl font-semibold text-primary-700">Rp {{ number_format($totalNilai, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        <div>
            <button wire:click="simpan" wire:loading.attr="disabled"
                    class="w-full rounded-lg bg-primary-500 px-6 py-2.5 text-sm font-semibold text-white hover:bg-primary-700 disabled:opacity-60 sm:w-auto sm:float-right">
                <span wire:loading.remove wire:target="simpan">Simpan & Kredit Saldo</span>
                <span wire:loading wire:target="simpan">Memproses…</span>
            </button>
        </div>
    @endif

    {{-- Riwayat hari ini --}}
    @if ($riwayat->isNotEmpty())
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 px-4 py-4 sm:px-6">
                <p class="text-sm font-semibold text-primary-900">Setoran hari ini</p>
            </div>

            {{-- Tabel (desktop) --}}
            <table class="hidden w-full text-left text-sm sm:table">
                <tbody class="divide-y divide-gray-100">
                    @foreach ($riwayat as $d)
                        <tr>
                            <td class="px-6 py-3 text-primary-900">{{ $d->nasabah?->name ?? '—' }}</td>
                            <td class="px-6 py-3 text-gray-500">{{ number_format($d->total_berat, 2, ',', '.') }} kg</td>
                            <td class="px-6 py-3 text-right font-medium text-primary-700">Rp {{ number_format($d->total_nilai, 0, ',', '.') }}</td>
                            <td class="px-6 py-3 text-right text-xs text-gray-400">{{ $d->created_at->format('H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Kartu (mobile) --}}
            <div class="divide-y divide-gray-100 sm:hidden">
                @foreach ($riwayat as $d)
                    <div class="flex items-center justify-between gap-2 px-4 py-3">
                        <div class="min-w-0">
                            <p class="truncate font-medium text-primary-900">{{ $d->nasabah?->name ?? '—' }}</p>
                            <p class="text-xs text-gray-400">{{ number_format($d->total_berat, 2, ',', '.') }} kg · {{ $d->created_at->format('H:i') }}</p>
                        </div>
                        <p class="shrink-0 font-medium text-primary-700">Rp {{ number_format($d->total_nilai, 0, ',', '.') }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Popup kesalahan QR/NIK --}}
    <x-modal active="showError" max-width="max-w-sm">
        <div class="px-6 py-6 text-center">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100 text-red-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/>
                </svg>
            </div>
            <h3 class="mt-4 text-base font-semibold text-primary-900">QR / NIK Tidak Dikenali</h3>
            <p class="mt-1 text-sm text-gray-500">{{ $errorMsg }}</p>
            <button type="button" wire:click="$set('showError', false)" class="mt-5 w-full rounded-lg bg-primary-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-700">Coba Lagi</button>
        </div>
    </x-modal>
</div>