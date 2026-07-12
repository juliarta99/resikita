<x-layouts.app>
    <h1 class="text-xl font-semibold text-gray-900">Dashboard</h1>
    <p class="mt-2 text-gray-600">
        Halo, {{ auth()->user()->name }}. Peran Anda: {{ auth()->user()->getRoleNames()->implode(', ') }}.
    </p>
    <p class="mt-4 text-sm text-gray-400">Dashboard spesifik per role akan dibangun pada fase berikutnya.</p>
</x-layouts.app>
