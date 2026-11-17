<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <x-page-header title="Admin Dashboard" :subtitle="'Welcome back, '.auth()->user()->name.'. Here is the clinic at a glance.'" />

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-3 xl:grid-cols-6">
            <x-stat-card label="Patients" :value="$stats['patients']" :href="route('admin.patients.index')" />
            <x-stat-card label="Doctors" :value="$stats['doctors']" :href="route('admin.doctors.index')" />
            <x-stat-card label="Today" :value="$stats['today']" hint="appointments" :href="route('admin.appointments.index', ['from' => today()->toDateString(), 'to' => today()->toDateString()])" />
            <x-stat-card label="Upcoming" :value="$stats['upcoming']" hint="booked appointments" :href="route('admin.appointments.index', ['status' => 'booked'])" />
            <x-stat-card label="Open slots" :value="$stats['openSlots']" hint="ready to book" :href="route('admin.rosters.index', ['status' => 'available'])" />
            <x-stat-card label="Records" :value="$stats['records']" hint="medical records" :href="route('admin.records.index')" />
        </div>

        <x-card title="Next appointments" :padding="false">
            <x-slot name="actions">
                <x-button :href="route('admin.appointments.index')" variant="ghost" size="sm">View all</x-button>
            </x-slot>

            <x-table empty="No upcoming appointments.">
                @if ($nextAppointments->isNotEmpty())
                    <x-slot name="head">
                        <tr>
                            <th>ID</th>
                            <th>Date and time</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Service</th>
                            <th>Status</th>
                        </tr>
                    </x-slot>
                @endif

                @foreach ($nextAppointments as $appointment)
                    <tr>
                        <td>{{ $appointment->appointment_code }}</td>
                        <td>{{ $appointment->slot->slot_date->format('d M Y') }}, {{ $appointment->slot->start_time_label }}</td>
                        <td>{{ $appointment->patient->name }}</td>
                        <td>{{ $appointment->slot->doctor->name }}</td>
                        <td>{{ $appointment->service->name }}</td>
                        <td><x-badge :status="$appointment->status->value" /></td>
                    </tr>
                @endforeach
            </x-table>
        </x-card>

        <div class="flex flex-wrap gap-2">
            <x-button :href="route('admin.rosters.create')">Add slot</x-button>
            <x-button :href="route('admin.doctors.create')" variant="secondary">Add doctor</x-button>
        </div>
    </div>
</x-app-layout>
