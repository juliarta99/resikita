@props(['active', 'maxWidth' => 'max-w-lg'])

<div
    x-data="{ open: $wire.entangle('{{ $active }}') }"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
>
    <div x-show="open" x-transition.opacity class="fixed inset-0 bg-primary-900/40"></div>

    <div class="flex min-h-full items-start justify-center p-4 sm:items-center">
        <div
            x-show="open"
            x-transition
            @click.outside="open = false"
            @keydown.escape.window="open = false"
            class="relative w-full {{ $maxWidth }} rounded-2xl bg-white shadow-xl"
        >
            {{ $slot }}
        </div>
    </div>
</div>