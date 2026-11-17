<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $code }} {{ $title }} | {{ config('app.name') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        {{-- An error page must still render if the frontend build is missing. --}}
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css'])
        @endif
    </head>
    <body class="font-sans antialiased">
        <main class="flex min-h-screen flex-col items-center justify-center bg-canvas px-4 text-center">
            <img src="{{ asset('images/logo/logo-full-color.png') }}" alt="{{ config('app.name') }}" class="h-12 w-auto">

            <p class="mt-10 text-6xl font-semibold text-primary">{{ $code }}</p>
            <h1 class="mt-3 text-xl font-semibold text-deep">{{ $title }}</h1>
            <p class="mt-2 max-w-md text-sm text-muted">{{ $message }}</p>

            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <a href="{{ url('/') }}" class="inline-flex items-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary-hover">Go to home</a>
                @if (empty($hideBack))
                    <button type="button" onclick="history.back()" class="inline-flex items-center rounded-md border border-line bg-white px-4 py-2 text-sm font-medium text-ink hover:bg-canvas">Go back</button>
                @endif
            </div>
        </main>
    </body>
</html>
