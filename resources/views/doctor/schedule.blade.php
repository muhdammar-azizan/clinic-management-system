<x-app-layout>
    @php($weekEnd = $weekStart->copy()->addDays(6))

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <x-page-header title="My Schedule" subtitle="Your appointment slots. The clinic admin manages the roster." />

        <x-card>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <x-button :href="route('doctor.schedule.index', ['week' => $weekStart->copy()->subWeek()->toDateString()])" variant="secondary" size="sm">&larr; Prev</x-button>
                    <x-button :href="route('doctor.schedule.index')" variant="secondary" size="sm">This week</x-button>
                    <x-button :href="route('doctor.schedule.index', ['week' => $weekStart->copy()->addWeek()->toDateString()])" variant="secondary" size="sm">Next &rarr;</x-button>
                    <span class="ms-2 text-sm font-semibold text-deep">{{ $weekStart->format('d M') }} &ndash; {{ $weekEnd->format('d M Y') }}</span>
                </div>

                <p class="text-sm text-muted">
                    <span class="font-medium text-ink">{{ $totals['slots'] }}</span> slots &middot;
                    <span class="font-medium text-ink">{{ $totals['available'] }}</span> available &middot;
                    <span class="font-medium text-ink">{{ $totals['booked'] }}</span> booked
                </p>
            </div>
        </x-card>

        <div class="grid gap-3 md:grid-cols-7">
            @foreach ($days as $day)
                @php($slots = $slotsByDay->get($day->toDateString(), collect()))
                <section @class([
                    'min-h-32 rounded-card border bg-white shadow-card',
                    'border-primary' => $day->isToday(),
                    'border-line' => ! $day->isToday(),
                ])>
                    <header @class([
                        'border-b px-3 py-2 text-xs font-semibold uppercase tracking-wide',
                        'border-primary bg-primary-soft text-deep' => $day->isToday(),
                        'border-line text-muted' => ! $day->isToday(),
                    ])>
                        {{ $day->format('D') }} <span class="text-sm normal-case text-ink">{{ $day->format('d M') }}</span>
                    </header>

                    <ul class="space-y-1.5 p-2">
                        @forelse ($slots as $slot)
                            @php($appointment = $slot->activeAppointment)
                            <li @class([
                                'rounded border px-2 py-1.5 text-xs leading-tight',
                                'border-success/30 bg-success-soft text-success-dark' => $slot->isAvailable(),
                                'border-primary/30 bg-primary-soft text-deep' => ! $slot->isAvailable(),
                            ]) title="{{ $slot->slot_code }}{{ $slot->notes ? ' - '.$slot->notes : '' }}">
                                <span class="font-semibold">{{ $slot->start_time_label }}</span>
                                @if ($appointment)
                                    <span class="block truncate">{{ $appointment->patient->name }}</span>
                                    <span class="block truncate text-[11px] opacity-80">{{ $appointment->service->name }}</span>
                                @else
                                    <span class="block text-[11px] opacity-80">{{ $slot->status->label() }}</span>
                                @endif
                            </li>
                        @empty
                            <li class="px-1 py-2 text-xs text-muted">No slots</li>
                        @endforelse
                    </ul>
                </section>
            @endforeach
        </div>

        <div class="flex flex-wrap gap-4 text-xs text-muted">
            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-success"></span> Available</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-primary"></span> Occupied or booked</span>
        </div>
    </div>
</x-app-layout>
