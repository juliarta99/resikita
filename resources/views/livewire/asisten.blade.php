@php use App\Enums\PeranChat; @endphp

<div>
    <x-ui.kepala-halaman
        judul="Asisten lingkungan"
        keterangan="Tanya soal pemilahan, kompos, bank sampah, limbah B3, atau retribusi sampah. Jawabannya bisa didengarkan.">
        <x-slot:aksi>
            <x-ui.tombol wire:click="sesiBaru" ikon="plus">Percakapan baru</x-ui.tombol>
        </x-slot:aksi>
    </x-ui.kepala-halaman>

    <div class="grid gap-6 lg:grid-cols-4">

        {{-- Daftar sesi --}}
        <x-ui.kartu padat judul="Riwayat percakapan">
            @if ($daftarSesi->isEmpty())
                <p class="px-5 pb-5 text-sm text-gray-500">
                    Belum ada percakapan. Ajukan pertanyaan pertama Anda di sebelah kanan.
                </p>
            @else
                <ul class="divide-y divide-gray-100">
                    @foreach ($daftarSesi as $item)
                        <li class="flex items-start gap-2 px-3 py-2">
                            <button type="button" wire:click="pilihSesi({{ $item->id }})"
                                    @if ($sesiId === $item->id) aria-current="true" @endif
                                    class="min-w-0 flex-1 rounded-lg px-2 py-1.5 text-left transition
                                           {{ $sesiId === $item->id ? 'bg-primary-50' : 'hover:bg-gray-50' }}">
                                <span class="block truncate text-sm font-medium text-primary-900">
                                    {{ $item->judul }}
                                </span>
                                <span class="block text-xs text-gray-500">
                                    {{ $item->pesan_count }} pesan
                                    &middot; {{ $item->terakhir_at?->diffForHumans(short: true) }}
                                </span>
                            </button>

                            <button type="button" wire:click="hapusSesi({{ $item->id }})"
                                    wire:confirm="Hapus percakapan ini? Tindakan ini tidak dapat dibatalkan."
                                    class="rounded-lg p-1.5 text-gray-400 transition hover:bg-red-50 hover:text-red-600"
                                    aria-label="Hapus percakapan {{ $item->judul }}">
                                <x-ui.ikon nama="sampah" class="h-3.5 w-3.5"/>
                            </button>
                        </li>
                    @endforeach
                </ul>

                <div class="border-t border-gray-100 px-5 py-3">{{ $daftarSesi->links() }}</div>
            @endif
        </x-ui.kartu>

        {{-- Percakapan --}}
        <x-ui.kartu padat class="flex flex-col lg:col-span-3">
            <div class="flex-1 space-y-4 p-5">
                @if ($sesi === null || $sesi->pesan->isEmpty())
                    <div class="py-6 text-center">
                        <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-primary-50 text-primary-600">
                            <x-ui.ikon nama="megafon" class="h-6 w-6"/>
                        </span>
                        <p class="mt-4 text-sm font-semibold text-primary-900">Mulai dari salah satu ini</p>
                        <p class="mt-1 text-sm text-gray-500">
                            Atau tanyakan apa pun soal pengelolaan sampah di wilayah Anda.
                        </p>

                        <div class="mx-auto mt-5 flex max-w-2xl flex-wrap justify-center gap-2">
                            @foreach ($saran as $pertanyaan)
                                <button type="button" wire:click="$set('pesan', @js($pertanyaan))"
                                        class="rounded-full border border-gray-200 px-3.5 py-1.5 text-left text-sm
                                               text-gray-700 transition hover:border-primary-200 hover:bg-primary-50">
                                    {{ $pertanyaan }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @else
                    @foreach ($sesi->pesan as $pesanItem)
                        @if ($pesanItem->role === PeranChat::User)
                            <div class="flex justify-end">
                                <div class="max-w-2xl rounded-2xl rounded-br-md bg-primary-500 px-4 py-3">
                                    <p class="whitespace-pre-line text-sm text-white">{{ $pesanItem->konten }}</p>
                                    @if ($pesanItem->sumber_input === \App\Enums\SumberInput::Suara)
                                        <p class="mt-1.5 flex items-center gap-1 text-xs text-primary-100">
                                            <x-ui.ikon nama="suara" class="h-3 w-3"/>
                                            Didiktekan
                                        </p>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="flex justify-start">
                                <div x-data="pembacaJawaban(@js($pesanItem->konten))"
                                     class="max-w-2xl rounded-2xl rounded-bl-md bg-gray-100 px-4 py-3">
                                    <p class="whitespace-pre-line text-sm text-gray-800">{{ $pesanItem->konten }}</p>

                                    <div class="mt-2 flex items-center gap-2">
                                        <button type="button" @click="putarAtauHenti()"
                                                x-show="didukung"
                                                class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1 text-xs
                                                       font-medium text-primary-700 transition hover:bg-primary-50"
                                                x-bind:aria-label="sedangMemutar ? 'Hentikan pembacaan jawaban' : 'Dengarkan jawaban ini'">
                                            <x-ui.ikon nama="suara" class="h-3.5 w-3.5"/>
                                            <span x-text="sedangMemutar ? 'Hentikan' : 'Dengarkan'"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                @endif

                {{-- Penanda tunggu --}}
                <div wire:loading wire:target="kirim" class="flex justify-start" role="status" aria-live="polite">
                    <div class="rounded-2xl rounded-bl-md bg-gray-100 px-4 py-3">
                        <span class="sr-only">Sedang menyusun jawaban</span>
                        <span class="flex gap-1" aria-hidden="true">
                            @foreach (range(1, 3) as $titik)
                                <span class="h-2 w-2 animate-pulse rounded-full bg-gray-400"
                                      style="animation-delay: {{ ($titik - 1) * 150 }}ms"></span>
                            @endforeach
                        </span>
                    </div>
                </div>
            </div>

            {{-- Kotak kirim --}}
            <form wire:submit="kirim" class="border-t border-gray-100 p-4"
                  x-data="pendiktean()">
                <div class="flex items-end gap-2">
                    <div class="flex-1">
                        <label for="pesan-asisten" class="sr-only">Pertanyaan Anda</label>
                        <textarea id="pesan-asisten" wire:model="pesan" rows="2"
                                  x-ref="kotak"
                                  placeholder="Tulis pertanyaan, atau tekan tombol mikrofon untuk mendiktekan"
                                  class="block w-full resize-none rounded-xl border px-3.5 py-2.5 text-sm shadow-sm
                                         focus:outline-none focus:ring-4
                                         {{ $errors->has('pesan')
                                             ? 'border-red-300 focus:border-red-500 focus:ring-red-200'
                                             : 'border-gray-300 focus:border-primary-500 focus:ring-primary-100' }}"></textarea>

                        @error('pesan')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @enderror

                        <p class="mt-1 text-xs text-gray-500" role="status" aria-live="polite"
                           x-text="keterangan" x-show="keterangan"></p>
                    </div>

                    <button type="button" @click="alihkan()" x-show="didukung"
                            class="rounded-xl p-3 transition"
                            :class="merekam ? 'bg-red-50 text-red-600' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                            x-bind:aria-label="merekam ? 'Hentikan pendiktean' : 'Diktekan pertanyaan'"
                            x-bind:aria-pressed="merekam.toString()">
                        <x-ui.ikon nama="suara" class="h-5 w-5"/>
                    </button>

                    <x-ui.tombol tipe="submit" class="h-[46px]"
                                 wire:loading.attr="disabled" wire:target="kirim">
                        Kirim
                    </x-ui.tombol>
                </div>
            </form>
        </x-ui.kartu>
    </div>
</div>
