<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="flex min-h-screen">
            {{-- Brand panel, desktop only --}}
            <aside class="hidden w-1/2 max-w-xl flex-col justify-between bg-deep p-10 text-white lg:flex">
                <a href="/"><img src="{{ asset('images/logo/logo-full-white.png') }}" alt="{{ config('app.name') }}" class="h-12 w-auto"></a>
                <div>
                    <h2 class="text-3xl font-semibold leading-tight text-white">Book visits, manage schedules and keep records in one place.</h2>
                    <p class="mt-3 text-sm text-white/70">For patients, doctors and clinic administrators.</p>
                </div>
                <p class="text-xs text-white/50">&copy; {{ date('Y') }} {{ config('app.name') }}</p>
            </aside>

            <div class="flex flex-1 flex-col items-center justify-center bg-canvas px-4 py-10">
                <a href="/" class="mb-6 lg:hidden">
                    <img src="{{ asset('images/logo/logo-full-color.png') }}" alt="{{ config('app.name') }}" class="h-12 w-auto">
                </a>

                <div class="w-full max-w-md rounded-card border border-line bg-white p-6 shadow-card sm:p-8">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
