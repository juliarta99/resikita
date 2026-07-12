<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Niti Resik' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-gray-50 text-gray-900">
    <header class="bg-white border-b border-gray-200">
        <div class="max-w-6xl mx-auto px-4 h-14 flex items-center justify-between">
            <span class="font-bold text-emerald-900">Niti Resik</span>
            <div class="flex items-center gap-4 text-sm">
                <span class="text-gray-600">{{ auth()->user()?->name }}</span>
                <form method="POST" action="/logout">
                    @csrf
                    <button type="submit" class="text-gray-500 hover:text-gray-900">Keluar</button>
                </form>
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 py-8">
        {{ $slot }}
    </main>
</body>
</html>
