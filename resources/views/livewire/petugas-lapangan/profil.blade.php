<div class="space-y-5">
    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Profil Saya</h1>
        <p class="text-slate-500 text-sm">Kelola informasi akun dan keamanan Anda.</p>
    </div>

    {{-- Kartu identitas --}}
    <div class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5">
        <div class="flex items-center gap-4">
            @php
                $inisial = collect(explode(' ', trim($user->name ?? '?')))
                    ->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->join('');
            @endphp
            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-lg font-bold text-white sm:h-16 sm:w-16 sm:text-xl">
                {{ strtoupper($inisial ?: '?') }}
            </div>
            <div class="min-w-0">
                <p class="truncate text-lg font-bold text-slate-800">{{ $user->name }}</p>
                <p class="text-sm text-slate-500">Petugas Lapangan</p>
                @if ($user->phone)
                    <p class="text-xs text-slate-400">{{ $user->phone }}</p>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:gap-5 lg:grid-cols-2">
        {{-- Edit profil --}}
        <div class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5">
            <h2 class="mb-4 text-base font-bold text-slate-800">Informasi Akun</h2>
            @if (session('ok_profil'))
                <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('ok_profil') }}</div>
            @endif
            <form wire:submit="simpanProfil" class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Nama <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="name"
                        class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Nomor Telepon</label>
                    <input type="text" wire:model="phone" placeholder="08…"
                        class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    @error('phone') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                    <input type="email" wire:model="email" placeholder="nama@email.com"
                        class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    @error('email') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <button type="submit"
                    class="w-full rounded-lg bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800 sm:w-auto">
                    <span wire:loading.remove wire:target="simpanProfil">Simpan Perubahan</span>
                    <span wire:loading wire:target="simpanProfil">Menyimpan…</span>
                </button>
            </form>
        </div>

        {{-- Ubah password --}}
        <div class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5">
            <h2 class="mb-4 text-base font-bold text-slate-800">Ubah Password</h2>
            @if (session('ok_password'))
                <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('ok_password') }}</div>
            @endif
            <form wire:submit="ubahPassword" class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Password Lama <span class="text-red-500">*</span></label>
                    <input type="password" wire:model="password_lama"
                        class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    @error('password_lama') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Password Baru <span class="text-red-500">*</span></label>
                    <input type="password" wire:model="password" placeholder="Minimal 8 karakter"
                        class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    @error('password') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Konfirmasi Password Baru <span class="text-red-500">*</span></label>
                    <input type="password" wire:model="password_confirmation"
                        class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                </div>
                <button type="submit"
                    class="w-full rounded-lg border border-emerald-600 bg-emerald-50 px-5 py-2.5 text-sm font-semibold text-emerald-700 hover:bg-emerald-100 sm:w-auto">
                    <span wire:loading.remove wire:target="ubahPassword">Ubah Password</span>
                    <span wire:loading wire:target="ubahPassword">Menyimpan…</span>
                </button>
            </form>
        </div>
    </div>
</div>