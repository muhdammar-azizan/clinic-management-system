<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Style Guide - {{ config('app.name') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700|dm-sans:400,500,600,700|inter:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased"
          x-data="{ font: 'Figtree' }"
          :style="`font-family: '${font}', ui-sans-serif, system-ui, sans-serif`">

        {{-- Preview-only page. Not linked anywhere and only registered in the local environment. --}}
        <header class="sticky top-0 z-40 bg-deep text-white">
            <div class="mx-auto flex h-14 max-w-6xl items-center justify-between gap-4 px-4">
                <img src="{{ asset('images/logo/logo-full-white.png') }}" alt="{{ config('app.name') }}" class="h-10 w-auto">
                <span class="text-sm text-white/80">Style Guide</span>
            </div>
        </header>

        <main class="mx-auto max-w-6xl space-y-10 px-4 py-8">

            {{-- Fonts --}}
            <section class="space-y-4">
                <x-page-header title="Font comparison" subtitle="Pick a font. The whole page switches so you can judge it in context." />

                <div class="flex flex-wrap gap-2">
                    @foreach (['Figtree', 'DM Sans', 'Inter'] as $name)
                        <button type="button" @click="font = '{{ $name }}'"
                                :class="font === '{{ $name }}' ? 'bg-primary text-white border-primary' : 'bg-white text-ink border-line hover:bg-primary-soft'"
                                class="rounded-card border px-4 py-2 text-sm font-medium transition">
                            {{ $name }}
                        </button>
                    @endforeach
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    @foreach (['Figtree', 'DM Sans', 'Inter'] as $name)
                        <x-card :title="$name">
                            <div style="font-family: '{{ $name }}', sans-serif" class="space-y-2">
                                <p class="text-2xl font-semibold text-deep">Dr. Aisyah Rahman</p>
                                <p class="text-sm text-ink">Appointment booked for 24 Nov 2026, 10:30 AM. General Practitioner.</p>
                                <p class="text-xs text-muted">ABCDEFGHIJKLMNOPQRSTUVWXYZ abcdefghijklmnopqrstuvwxyz 0123456789</p>
                            </div>
                        </x-card>
                    @endforeach
                </div>
            </section>

            {{-- Colors --}}
            <section class="space-y-4">
                <x-page-header title="Colors" subtitle="Accent (orange) is only for one main call to action. Never for errors, delete or cancel." />
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
                    @foreach ([
                        'deep' => 'bg-deep', 'primary' => 'bg-primary', 'primary-soft' => 'bg-primary-soft',
                        'accent' => 'bg-accent', 'accent-soft' => 'bg-accent-soft', 'canvas' => 'bg-canvas',
                        'ink' => 'bg-ink', 'muted' => 'bg-muted', 'line' => 'bg-line',
                        'success' => 'bg-success', 'warning' => 'bg-warning', 'danger' => 'bg-danger',
                    ] as $name => $class)
                        <div class="overflow-hidden rounded-card border border-line bg-white">
                            <div class="h-14 {{ $class }}"></div>
                            <p class="px-3 py-2 text-xs font-medium text-ink">{{ $name }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Buttons --}}
            <section class="space-y-4">
                <x-page-header title="Buttons" />
                <x-card>
                    <div class="flex flex-wrap items-center gap-3">
                        <x-button variant="primary">Primary</x-button>
                        <x-button variant="secondary">Secondary</x-button>
                        <x-button variant="accent">Book Appointment</x-button>
                        <x-button variant="danger">Delete</x-button>
                        <x-button variant="ghost">Ghost</x-button>
                        <x-button size="sm">Small</x-button>
                        <x-button href="#" variant="secondary" size="sm">Link button</x-button>
                    </div>
                </x-card>
            </section>

            {{-- Badges and alerts --}}
            <section class="space-y-4">
                <x-page-header title="Badges and alerts" />
                <x-card>
                    <div class="flex flex-wrap gap-2">
                        @foreach (['available', 'booked', 'occupied', 'completed', 'pending', 'cancelled', 'critical'] as $status)
                            <x-badge :status="$status" />
                        @endforeach
                    </div>
                </x-card>
                <div class="grid gap-3 md:grid-cols-2">
                    <x-alert variant="success">Appointment booked successfully.</x-alert>
                    <x-alert variant="info">Your slot is held for 10 minutes.</x-alert>
                    <x-alert variant="warning">This doctor has only one slot left today.</x-alert>
                    <x-alert variant="danger">That slot was just taken. Please pick another.</x-alert>
                </div>
            </section>

            {{-- Form --}}
            <section class="space-y-4">
                <x-page-header title="Form fields" />
                <x-card title="Book an appointment">
                    <div class="grid gap-4 md:grid-cols-2">
                        <x-field label="Full name" name="name" required>
                            <x-text-input id="name" name="name" class="w-full" placeholder="Aisyah Rahman" />
                        </x-field>
                        <x-field label="Service" name="service" hint="Choose the type of consultation.">
                            <x-select id="service" name="service" class="w-full">
                                <option>General Practitioner</option>
                                <option>Pediatrician</option>
                                <option>Family Medicine</option>
                                <option>Gynecologist</option>
                            </x-select>
                        </x-field>
                        <x-field label="Notes" name="notes" class="md:col-span-2">
                            <x-textarea id="notes" name="notes" rows="3" class="w-full" />
                        </x-field>
                    </div>
                </x-card>
            </section>

            {{-- Table --}}
            <section class="space-y-4">
                <x-page-header title="Table" />
                <x-card :padding="false">
                    <x-table>
                        <x-slot name="head">
                            <tr>
                                <th>Patient</th>
                                <th>Service</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </x-slot>
                        @foreach ([
                            ['Aisyah Rahman', 'General Practitioner', '24 Nov 2026, 10:30', 'booked'],
                            ['Daniel Lim', 'Pediatrician', '24 Nov 2026, 11:00', 'completed'],
                            ['Siti Nur', 'Gynecologist', '25 Nov 2026, 09:00', 'cancelled'],
                        ] as [$patient, $service, $date, $status])
                            <tr>
                                <td class="font-medium">{{ $patient }}</td>
                                <td>{{ $service }}</td>
                                <td>{{ $date }}</td>
                                <td><x-badge :status="$status" /></td>
                                <td class="text-right">
                                    <x-button variant="danger" size="sm"
                                              @click="$dispatch('confirm-delete', { action: '#' })">Delete</x-button>
                                </td>
                            </tr>
                        @endforeach
                    </x-table>
                </x-card>

                <x-card title="Empty state">
                    <x-table empty="No appointments found."></x-table>
                </x-card>
            </section>
        </main>

        <x-confirm-modal />
    </body>
</html>
