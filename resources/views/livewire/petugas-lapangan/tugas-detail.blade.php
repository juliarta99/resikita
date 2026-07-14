<div class="space-y-5">
    {{-- Header --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('petugas.tugas') }}" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div class="min-w-0">
            <h1 class="text-xl font-bold text-slate-800">Detail Tugas</h1>
            <p class="truncate text-xs text-slate-400">{{ $report->tiket_no }}</p>
        </div>
    </div>
    @if (session('ok'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('ok') }}</div>
    @endif

    <div class="grid grid-cols-1 gap-4 sm:gap-5 lg:grid-cols-3">
        {{-- Kiri: info laporan (di HP tampil setelah form update) --}}
        <div class="order-2 space-y-4 sm:space-y-5 lg:order-1 lg:col-span-2">
            <div class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5">
                <div class="flex items-start justify-between gap-3">
                    <h2 class="text-lg font-bold text-slate-800">{{ $report->judul }}</h2>
                    @php
                        $rbadge = [
                            'menunggu'     => 'bg-slate-100 text-slate-600',
                            'diverifikasi' => 'bg-blue-100 text-blue-700',
                            'ditugaskan'   => 'bg-amber-100 text-amber-700',
                            'proses'       => 'bg-indigo-100 text-indigo-700',
                            'selesai'      => 'bg-emerald-100 text-emerald-700',
                            'ditolak'      => 'bg-red-100 text-red-700',
                        ][$report->status] ?? 'bg-slate-100 text-slate-600';
                    @endphp
                    <span class="shrink-0 rounded-md px-2 py-1 text-xs font-semibold capitalize {{ $rbadge }}">{{ $report->status }}</span>
                </div>
                <div class="mt-3 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <p class="text-xs text-slate-400">Kategori</p>
                        <p class="font-medium text-slate-700">{{ $report->kategori?->nama ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Pelapor</p>
                        <p class="font-medium text-slate-700">{{ $report->pelapor?->name ?? '—' }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <p class="text-xs text-slate-400">Alamat</p>
                        <p class="font-medium text-slate-700">{{ $report->alamat ?? '-' }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <p class="text-xs text-slate-400">Deskripsi</p>
                        <p class="text-slate-600">{{ $report->deskripsi }}</p>
                    </div>
                </div>
                @if ($report->foto)
                    <img src="{{ asset('storage/'.$report->foto) }}" class="mt-4 max-h-60 w-full rounded-lg object-cover sm:max-h-72">
                @endif
                @if ($report->lat && $report->lng)
                    <a href="https://www.google.com/maps/search/?api=1&query={{ $report->lat }},{{ $report->lng }}" target="_blank"
                       class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-lg border border-emerald-600 px-3 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-50 sm:w-auto">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Buka di Google Maps
                    </a>
                @endif
            </div>

            {{-- Timeline progress --}}
            <div class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5">
                <h2 class="mb-4 text-base font-bold text-slate-800">Riwayat Progress</h2>
                @forelse ($timeline as $p)
                    <div class="flex gap-3 border-l-2 border-emerald-100 pb-5 pl-4 last:pb-0">
                        <div class="-ml-[22px] mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded-full {{ $p->status_progress === 'selesai' ? 'bg-emerald-600' : 'bg-blue-500' }}">
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-sm font-semibold capitalize text-slate-800">{{ $p->status_progress }}</span>
                                <span class="shrink-0 text-xs text-slate-400">{{ $p->created_at?->format('d M Y H:i') }}</span>
                            </div>
                            <p class="mt-1 text-sm text-slate-600">{{ $p->catatan }}</p>
                            @if ($p->foto_bukti)
                                <img src="{{ asset('storage/'.$p->foto_bukti) }}" class="mt-2 h-32 w-32 rounded-lg object-cover">
                            @endif
                            <p class="mt-1 text-xs text-slate-400">oleh {{ $p->petugas?->name ?? 'Petugas' }}</p>
                        </div>
                    </div>
                @empty
                    <p class="py-6 text-center text-sm text-slate-400">Belum ada progress. Tambahkan update pertama Anda.</p>
                @endforelse
            </div>
        </div>

        {{-- Kanan: form update progress (di HP tampil paling atas) --}}
        <div class="order-1 lg:order-2 lg:col-span-1">
            <div class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5 lg:sticky lg:top-28">
                @if ($sudahSelesai)
                    {{-- Laporan sudah selesai: sembunyikan form, tampilkan pesan --}}
                    <div class="flex flex-col items-center py-6 text-center">
                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <h2 class="mt-3 text-base font-bold text-slate-800">Laporan Sudah Diselesaikan</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Tugas ini telah ditandai selesai dan tidak memerlukan pembaruan progress lagi.
                            Terima kasih atas penanganannya.
                        </p>
                        <a href="{{ route('petugas.tugas') }}"
                           class="mt-4 inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                            Kembali ke Daftar Tugas
                        </a>
                    </div>
                @else
                    <h2 class="mb-3 text-base font-bold text-slate-800">Update Progress</h2>
                    <form wire:submit="simpanProgress" class="mt-3 space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Status</label>
                            <select wire:model="statusProgress"
                                class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="dikerjakan">Dikerjakan</option>
                                <option value="selesai">Selesai</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Catatan <span class="text-red-500">*</span></label>
                            <textarea wire:model="catatan" rows="4" placeholder="Jelaskan tindakan / kondisi terkini…"
                                class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500"></textarea>
                            @error('catatan') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Foto Bukti (opsional)</label>
                            <input type="file" wire:model="fotoBukti" accept="image/*"
                                class="w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-emerald-700 hover:file:bg-emerald-100">
                            <div wire:loading wire:target="fotoBukti" class="mt-1 text-xs text-slate-400">Mengunggah…</div>
                            @error('fotoBukti') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            @if ($fotoBukti)
                                <img src="{{ $fotoBukti->temporaryUrl() }}" class="mt-2 h-24 w-24 rounded-lg object-cover">
                            @endif
                        </div>
                        <p class="text-xs text-slate-400">
                            Pelapor akan menerima notifikasi WhatsApp berisi pembaruan ini.
                        </p>
                        <button type="submit"
                            class="w-full rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">
                            <span wire:loading.remove wire:target="simpanProgress">Simpan Progress</span>
                            <span wire:loading wire:target="simpanProgress">Menyimpan…</span>
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>