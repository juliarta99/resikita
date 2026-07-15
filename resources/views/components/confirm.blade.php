@props([
    'active',
    'action',
    'title' => 'Konfirmasi',
    'message' => 'Tindakan ini tidak dapat dibatalkan.',
    'confirmLabel' => 'Hapus',
])

<div
    x-data="{ open: $wire.entangle('{{ $active }}') }"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
>
    <div x-show="open" x-transition.opacity class="fixed inset-0 bg-primary-900/40"></div>

    <div
        x-show="open"
        x-transition
        @click.outside="open = false"
        @keydown.escape.window="open = false"
        class="relative w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl"
    >
        <div class="flex items-start gap-3">
            <div class="flex h-10 w-10 flex-none items-center justify-center rounded-full bg-red-50">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 9v4m0 4h.01M10.29 3.86l-7.4 12.8A1.5 1.5 0 004.18 19h15.64a1.5 1.5 0 001.29-2.34l-7.4-12.8a1.5 1.5 0 00-2.42 0z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-base font-semibold text-primary-900">{{ $title }}</h3>
                <p class="mt-1 text-sm text-gray-500">{{ $message }}</p>
            </div>
        </div>

        <div class="mt-5 flex justify-end gap-2">
            <button type="button" @click="open = false"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-primary-900 hover:bg-gray-50">
                Batal
            </button>
            <button type="button" wire:click="{{ $action }}"
                    class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
                {{ $confirmLabel }}
            </button>
        </div>
    </div>
</div>