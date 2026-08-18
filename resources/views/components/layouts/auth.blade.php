@props(['title' => null])

<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · '.config('app.name') : config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/icon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full flex-col bg-gray-50 font-sans text-gray-800 antialiased">

<main class="flex flex-1 items-center justify-center px-4 py-10">
    <div class="w-full max-w-md">
        <a href="{{ route('publik.beranda') }}" class="mb-8 flex items-center justify-center gap-3">
            <x-ui.logo ukuran="besar"/>
            <span class="text-xl font-bold text-primary-700">{{ config('app.name') }}</span>
        </a>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
            {{ $slot }}
        </div>

        <p class="mt-6 text-center text-xs text-gray-500">
            Platform ekonomi sirkular pengelolaan sampah
        </p>
    </div>
</main>

</body>
</html>
