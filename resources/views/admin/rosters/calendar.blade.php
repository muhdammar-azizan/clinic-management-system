<x-app-layout>
    @php
        $query = fn ($week) => array_filter(['week' => $week->toDateString(), 'doctor' => $doctorId]);
        $weekEnd = $weekStart->copy()->addDays(6);
    @endphp

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <x-page-header title="Doctor Roster" subtitle="Weekly view of appointment slots.">
            <x-slot name="actions">
                @include('admin.rosters._tabs')
                <x-button :href="route('admin.rosters.create')">Add Slot</x-button>
            </x-slot>
        </x-page-header>

        <x-card>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <x-button :href="route('admin.rosters.calendar', $query($weekStart->copy()->subWeek()))" variant="secondary" size="sm" aria-label="Previous week">&larr; Prev</x-button>
                    <x-button :href="route('admin.rosters.calendar', array_filter(['doctor' => $doctorId]))" variant="secondary" size="sm">This week</x-button>
                    <x-button :href="route('admin.rosters.calendar', $query($weekStart->copy()->addWeek()))" variant="secondary" size="sm" aria-label="Next week">Next &rarr;</x-button>
                    <span class="ms-2 text-sm font-semibold text-deep">{{ $weekStart->format('d M') }} &ndash; {{ $weekEnd->format('d M Y') }}</span>
                </div>

                <form method="GET" class="flex items-center gap-2">
                    <input type="hidden" name="week" value="{{ $weekStart->toDateString() }}">
                    <x-select name="doctor" class="block w-52" onchange="this.form.submit()" aria-label="Filter by doctor">
                        <option value="">All doctors</option>
                        @foreach ($doctors as $doctor)
                            <option value="{{ $doctor->id }}" @selected($doctorId === $doctor->id)>{{ $doctor->name }}</option>
                        @endforeach
                    </x-select>
                </form>
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
                            @php($booked = $slot->active_appointment_exists)
                            <li>
                                {{-- Booked slots are locked, so they have no edit link. --}}
                                <a @if (! $booked) href="{{ route('admin.rosters.edit', $slot) }}" @endif
                                   @class([
                                       'block rounded border px-2 py-1.5 text-xs leading-tight',
                                       'border-success/30 bg-success-soft text-success-dark hover:border-success' => $slot->status->value === 'available',
                                       'border-primary/30 bg-primary-soft text-deep' => $slot->status->value === 'occupied',
                                       'cursor-default' => $booked,
                                   ])
                                   title="{{ $slot->slot_code }}{{ $slot->notes ? ' - '.$slot->notes : '' }}">
                                    <span class="font-semibold">{{ $slot->start_time_label }}</span>
                                    <span class="block truncate">{{ $slot->doctor->name }}</span>
                                    <span class="block text-[11px] opacity-80">{{ $slot->status->label() }}{{ $booked ? ' (booked)' : '' }}</span>
                                </a>
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
            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-primary"></span> Occupied</span>
        </div>
    </div>
</x-app-layout>
