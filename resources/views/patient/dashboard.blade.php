<x-app-layout>
    <div class="mx-auto max-w-5xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <x-page-header title="My Dashboard" :subtitle="'Welcome, '.auth()->user()->name.'.'">
            <x-slot name="actions">
                <x-button :href="route('patient.booking.create')" variant="accent">Book Appointment</x-button>
            </x-slot>
        </x-page-header>

        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif

        <div class="grid gap-3 sm:grid-cols-3">
            <x-stat-card label="Next appointment"
                         :value="$next ? $next->slot->slot_date->format('d M') : 'None'"
                         :hint="$next ? $next->slot->start_time_label.' with '.$next->doctor->name : 'Nothing booked yet'"
                         :href="route('patient.appointments.index')" />
            <x-stat-card label="Upcoming" :value="$upcomingCount" hint="booked appointments" :href="route('patient.appointments.index')" />
            <x-stat-card label="Medical records" :value="$recordsCount" hint="from your visits" :href="route('patient.records.index')" />
        </div>

        <x-card title="Upcoming appointments" :padding="false">
            <x-slot name="actions">
                <x-button :href="route('patient.appointments.index')" variant="ghost" size="sm">View all</x-button>
            </x-slot>

            <x-table empty="You have no upcoming appointments. Book one to get started.">
                @if ($upcoming->isNotEmpty())
                    <x-slot name="head">
                        <tr>
                            <th>Date and time</th>
                            <th>Doctor</th>
                            <th>Service</th>
                            <th>Status</th>
                        </tr>
                    </x-slot>
                @endif

                @foreach ($upcoming as $appointment)
                    <tr>
                        <td>{{ $appointment->slot->slot_date->format('d M Y') }}, {{ $appointment->slot->start_time_label }}</td>
                        <td>{{ $appointment->doctor->name }}</td>
                        <td>{{ $appointment->service->name }}</td>
                        <td><x-badge :status="$appointment->status->value" /></td>
                    </tr>
                @endforeach
            </x-table>
        </x-card>

        @if ($latestRecord)
            <x-card title="Latest medical record">
                <div class="flex flex-wrap items-center justify-between gap-3 text-sm">
                    <div>
                        <p class="font-medium text-ink">{{ $latestRecord->visit_date->format('d M Y') }} with {{ $latestRecord->doctor->name }}</p>
                        <p class="text-muted">{{ $latestRecord->chief_complaint }}</p>
                    </div>
                    <x-button :href="route('patient.records.show', $latestRecord)" variant="secondary" size="sm">View record</x-button>
                </div>
            </x-card>
        @endif
    </div>
</x-app-layout>
