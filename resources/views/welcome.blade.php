<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <header class="bg-deep">
            <div class="mx-auto flex h-14 max-w-6xl items-center justify-between px-4">
                <img src="{{ asset('images/logo/logo-full-white.png') }}" alt="{{ config('app.name') }}" class="h-10 w-auto">
                <nav class="flex items-center gap-2">
                    @auth
                        <x-button :href="route('dashboard')" variant="secondary" size="sm">Dashboard</x-button>
                    @else
                        <a href="{{ route('login') }}" class="px-3 text-sm font-medium text-white hover:text-white/80">Sign in</a>
                        <x-button :href="route('register')" variant="secondary" size="sm">Register</x-button>
                    @endauth
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-16 sm:py-24">
            <div class="max-w-2xl">
                <h1 class="text-4xl font-semibold leading-tight text-deep">Clinic care, organised.</h1>
                <p class="mt-4 text-base text-muted">Book appointments, manage doctor schedules and keep medical records in one simple system.</p>
                @guest
                    <div class="mt-8 flex flex-wrap gap-3">
                        <x-button :href="route('register')" variant="accent">Book Appointment</x-button>
                        <x-button :href="route('login')" variant="secondary">Sign in</x-button>
                    </div>
                @endguest
            </div>

            <div class="mt-16 grid gap-4 sm:grid-cols-3">
                <x-card title="Patients">Book a slot, track your visits and download prescriptions.</x-card>
                <x-card title="Doctors">See your schedule and keep clear medical records.</x-card>
                <x-card title="Admins">Manage rosters, patients and appointments.</x-card>
            </div>
        </main>
    </body>
</html>
