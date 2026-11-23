<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name') }} | Appointments, rosters and medical records</title>
        <meta name="description" content="Book appointments, manage doctor rosters and keep medical records in one secure clinic system for patients, doctors and admins.">
        <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        {{-- Header --}}
        <header class="sticky top-0 z-40 border-b border-white/10 bg-deep/95 backdrop-blur" x-data="{ open: false }">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4">
                <a href="{{ url('/') }}" class="flex items-center">
                    <img src="{{ asset('images/logo/logo-full-white.png') }}" alt="{{ config('app.name') }}" class="h-10 w-auto">
                </a>

                <nav class="hidden items-center gap-7 md:flex" aria-label="Main">
                    <a href="#features" class="text-sm font-medium text-white/80 hover:text-white">Features</a>
                    <a href="#how-it-works" class="text-sm font-medium text-white/80 hover:text-white">How it works</a>
                    <a href="#roles" class="text-sm font-medium text-white/80 hover:text-white">Who it is for</a>
                    <a href="#quality" class="text-sm font-medium text-white/80 hover:text-white">Quality</a>
                </nav>

                <div class="hidden items-center gap-2 md:flex">
                    @auth
                        <x-button :href="route('dashboard')" variant="secondary" size="sm">Go to dashboard</x-button>
                    @else
                        <a href="{{ route('login') }}" class="px-3 text-sm font-medium text-white hover:text-white/80">Sign in</a>
                        <x-button :href="route('register')" variant="accent" size="sm">Register</x-button>
                    @endauth
                </div>

                <button type="button" class="rounded-md p-2 text-white hover:bg-white/10 md:hidden" @click="open = !open" :aria-expanded="open" aria-label="Toggle menu">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
            </div>

            <div x-show="open" x-cloak class="border-t border-white/10 px-4 pb-4 md:hidden">
                <div class="flex flex-col gap-1 py-3">
                    <a href="#features" @click="open = false" class="rounded px-2 py-2 text-sm font-medium text-white/90 hover:bg-white/10">Features</a>
                    <a href="#how-it-works" @click="open = false" class="rounded px-2 py-2 text-sm font-medium text-white/90 hover:bg-white/10">How it works</a>
                    <a href="#roles" @click="open = false" class="rounded px-2 py-2 text-sm font-medium text-white/90 hover:bg-white/10">Who it is for</a>
                    <a href="#quality" @click="open = false" class="rounded px-2 py-2 text-sm font-medium text-white/90 hover:bg-white/10">Quality</a>
                </div>
                <div class="flex gap-2">
                    @auth
                        <x-button :href="route('dashboard')" variant="secondary" class="flex-1">Go to dashboard</x-button>
                    @else
                        <x-button :href="route('login')" variant="secondary" class="flex-1">Sign in</x-button>
                        <x-button :href="route('register')" variant="accent" class="flex-1">Register</x-button>
                    @endauth
                </div>
            </div>
        </header>

        <main>
            {{-- Hero --}}
            <section class="relative overflow-hidden bg-deep text-white">
                <div class="pointer-events-none absolute inset-0 opacity-40" style="background: radial-gradient(60rem 30rem at 85% -10%, #0A6FD1 0%, transparent 60%), radial-gradient(40rem 25rem at -10% 110%, #0A6FD1 0%, transparent 60%);"></div>
                <div class="relative mx-auto grid max-w-6xl items-center gap-12 px-4 py-16 sm:py-24 lg:grid-cols-2">
                    <div>
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-semibold text-white">
                            <span class="h-2 w-2 rounded-full bg-success"></span> Patients, doctors and admins in one place
                        </span>
                        <h1 class="mt-6 text-4xl font-extrabold leading-tight !text-white sm:text-5xl">
                            Clinic care,<br>organised from booking to follow-up.
                        </h1>
                        <p class="mt-5 max-w-xl text-lg text-white/80">
                            Patients book in a few clicks. Doctors see their day at a glance. Admins keep rosters and records under control. All in one secure system.
                        </p>
                        <div class="mt-8 flex flex-wrap gap-3">
                            @auth
                                <x-button :href="route('dashboard')" variant="accent" class="!px-6 !py-3 !text-base">Go to dashboard</x-button>
                            @else
                                <x-button :href="route('register')" variant="accent" class="!px-6 !py-3 !text-base">Book an appointment</x-button>
                                <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-md border border-white/30 px-6 py-3 text-base font-semibold !text-white transition hover:bg-white/10">Sign in</a>
                            @endauth
                        </div>
                        <dl class="mt-10 grid max-w-md grid-cols-3 gap-6 border-t border-white/15 pt-6">
                            <div><dt class="text-xs text-white/60">Roles</dt><dd class="text-2xl font-bold">3</dd></div>
                            <div><dt class="text-xs text-white/60">Modules</dt><dd class="text-2xl font-bold">4</dd></div>
                            <div><dt class="text-xs text-white/60">Automated tests</dt><dd class="text-2xl font-bold">378</dd></div>
                        </dl>
                    </div>

                    {{-- Product preview --}}
                    <div class="relative" aria-hidden="true">
                        <div class="rounded-xl bg-white p-4 text-ink shadow-pop ring-1 ring-black/5">
                            <div class="flex items-center justify-between border-b border-line pb-3">
                                <div>
                                    <p class="text-xs text-muted">Doctor dashboard</p>
                                    <p class="text-sm font-semibold text-deep">Today's appointments</p>
                                </div>
                                <span class="rounded-full bg-primary-soft px-2.5 py-1 text-xs font-semibold text-primary">3 visits</span>
                            </div>
                            <ul class="divide-y divide-line">
                                <li class="flex items-center justify-between py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-primary-soft text-xs font-bold text-primary">AH</span>
                                        <div><p class="text-sm font-semibold">Aina Hassan</p><p class="text-xs text-muted">09:00 · General consultation</p></div>
                                    </div>
                                    <span class="rounded-full bg-success-soft px-2.5 py-1 text-xs font-semibold text-success-dark">Completed</span>
                                </li>
                                <li class="flex items-center justify-between py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-primary-soft text-xs font-bold text-primary">RK</span>
                                        <div><p class="text-sm font-semibold">Rajesh Kumar</p><p class="text-xs text-muted">09:30 · Follow-up</p></div>
                                    </div>
                                    <span class="rounded-full bg-primary-soft px-2.5 py-1 text-xs font-semibold text-primary">Confirmed</span>
                                </li>
                                <li class="flex items-center justify-between py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-primary-soft text-xs font-bold text-primary">LW</span>
                                        <div><p class="text-sm font-semibold">Lim Wei</p><p class="text-xs text-muted">10:00 · Check-up</p></div>
                                    </div>
                                    <span class="rounded-full bg-warning-soft px-2.5 py-1 text-xs font-semibold text-warning-dark">Pending</span>
                                </li>
                            </ul>
                        </div>
                        <div class="absolute -bottom-6 -left-4 hidden w-56 rounded-lg bg-white p-3 text-ink shadow-pop ring-1 ring-black/5 sm:block">
                            <p class="text-xs text-muted">Medical record</p>
                            <p class="mt-1 text-sm font-semibold text-deep">Diagnosis and prescription saved</p>
                            <div class="mt-2 flex items-center gap-1 text-xs font-semibold text-success-dark">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                Visible to the patient
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Features --}}
            <section id="features" class="scroll-mt-16 mx-auto max-w-6xl px-4 py-20">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-sm font-semibold uppercase tracking-wide text-primary">Features</p>
                    <h2 class="mt-2 text-3xl font-bold sm:text-4xl">Everything a small clinic needs</h2>
                    <p class="mt-3 text-muted">Four connected modules replace paper schedules and loose files.</p>
                </div>

                <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @php
                        $features = [
                            ['Accounts and profiles', 'Register, verify your email, sign in safely and keep your profile up to date.', 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                            ['Appointment booking', 'Choose a service and doctor, see real free slots and book or cancel without a phone call.', 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                            ['Doctor roster', 'Admins plan working hours per doctor. Overlaps are blocked, so double booking cannot happen.', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                            ['Medical records', 'Doctors record diagnosis, notes and prescriptions. Patients see only their own history.', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                        ];
                    @endphp
                    @foreach ($features as [$title, $text, $icon])
                        <div class="rounded-card border border-line bg-white p-6 shadow-card transition hover:-translate-y-0.5 hover:shadow-pop">
                            <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-primary-soft text-primary">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                            </span>
                            <h3 class="mt-4 text-base">{{ $title }}</h3>
                            <p class="mt-2 text-sm text-muted">{{ $text }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- How it works --}}
            <section id="how-it-works" class="scroll-mt-16 bg-primary-soft/60">
                <div class="mx-auto max-w-6xl px-4 py-20">
                    <div class="mx-auto max-w-2xl text-center">
                        <p class="text-sm font-semibold uppercase tracking-wide text-primary">How it works</p>
                        <h2 class="mt-2 text-3xl font-bold sm:text-4xl">From booking to record in four steps</h2>
                    </div>
                    <ol class="mt-12 grid gap-6 md:grid-cols-4">
                        @foreach ([
                            ['Create an account', 'Register in a minute and verify your email.'],
                            ['Pick a slot', 'Choose a service, a doctor and a free time.'],
                            ['Visit the clinic', 'The doctor sees the appointment on their schedule.'],
                            ['Get your record', 'Diagnosis and prescription appear in your history.'],
                        ] as $i => [$title, $text])
                            <li class="relative rounded-card bg-white p-6 shadow-card">
                                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-deep text-sm font-bold text-white">{{ $i + 1 }}</span>
                                <h3 class="mt-4 text-base">{{ $title }}</h3>
                                <p class="mt-2 text-sm text-muted">{{ $text }}</p>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </section>

            {{-- Roles --}}
            <section id="roles" class="scroll-mt-16 mx-auto max-w-6xl px-4 py-20">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-sm font-semibold uppercase tracking-wide text-primary">Who it is for</p>
                    <h2 class="mt-2 text-3xl font-bold sm:text-4xl">One system, three views</h2>
                </div>
                <div class="mt-12 grid gap-6 lg:grid-cols-3">
                    @foreach ([
                        ['Patients', 'Book and manage visits.', ['Book and cancel appointments', 'See upcoming and past visits', 'Read your own medical records', 'Send feedback about the system']],
                        ['Doctors', 'Run the day with less paperwork.', ['See today\'s schedule', 'Mark visits as completed', 'Write diagnosis and prescriptions', 'Find patients quickly']],
                        ['Admins', 'Keep the clinic running.', ['Manage doctors and patients', 'Plan the doctor roster', 'Oversee all appointments', 'Review system quality and feedback']],
                    ] as [$role, $tagline, $points])
                        <div class="flex flex-col rounded-card border border-line bg-white p-6 shadow-card">
                            <h3 class="text-xl">{{ $role }}</h3>
                            <p class="mt-1 text-sm text-muted">{{ $tagline }}</p>
                            <ul class="mt-5 space-y-3">
                                @foreach ($points as $point)
                                    <li class="flex items-start gap-2 text-sm">
                                        <svg class="mt-0.5 h-5 w-5 flex-none text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        {{ $point }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Quality --}}
            <section id="quality" class="scroll-mt-16 bg-deep text-white">
                <div class="mx-auto grid max-w-6xl items-center gap-10 px-4 py-20 lg:grid-cols-2">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wide text-white/70">Built for quality</p>
                        <h2 class="mt-2 text-3xl font-bold !text-white sm:text-4xl">Trustworthy with health data</h2>
                        <p class="mt-4 text-white/80">
                            Medical information needs care. The system is covered by automated tests, static analysis and a continuous integration pipeline that runs on every change.
                        </p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        @foreach ([
                            ['Role-based access', 'Each role sees only its own pages and data.'],
                            ['Private records', 'Medical files are stored outside the public web folder.'],
                            ['Automated tests', 'Feature and quality tests run on every push.'],
                            ['Health monitoring', 'A health endpoint reports if the database is up.'],
                        ] as [$title, $text])
                            <div class="rounded-card border border-white/15 bg-white/5 p-4">
                                <h3 class="text-sm !text-white">{{ $title }}</h3>
                                <p class="mt-1 text-xs text-white/70">{{ $text }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- Call to action --}}
            <section class="mx-auto max-w-6xl px-4 py-20">
                <div class="rounded-2xl bg-gradient-to-br from-primary to-deep px-6 py-14 text-center text-white shadow-pop sm:px-12">
                    <h2 class="text-3xl font-bold !text-white sm:text-4xl">Ready to book your next visit?</h2>
                    <p class="mx-auto mt-3 max-w-xl text-white/80">Create a free account and book your first appointment in under two minutes.</p>
                    <div class="mt-8 flex flex-wrap justify-center gap-3">
                        @auth
                            <x-button :href="route('dashboard')" variant="accent" class="!px-6 !py-3 !text-base">Go to dashboard</x-button>
                        @else
                            <x-button :href="route('register')" variant="accent" class="!px-6 !py-3 !text-base">Create an account</x-button>
                            <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-md border border-white/40 px-6 py-3 text-base font-semibold !text-white transition hover:bg-white/10">Sign in</a>
                        @endauth
                    </div>
                </div>
            </section>
        </main>

        {{-- Footer --}}
        <footer class="border-t border-line bg-white">
            <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-4 py-8 sm:flex-row">
                <img src="{{ asset('images/logo/logo-full-color.png') }}" alt="{{ config('app.name') }}" class="h-9 w-auto">
                <p class="text-xs text-muted">&copy; {{ date('Y') }} {{ config('app.name') }}. A portfolio project built with Laravel.</p>
                <nav class="flex gap-5 text-xs font-medium">
                    <a href="{{ route('login') }}">Sign in</a>
                    <a href="{{ route('register') }}">Register</a>
                    <a href="#features">Features</a>
                </nav>
            </div>
        </footer>
    </body>
</html>
