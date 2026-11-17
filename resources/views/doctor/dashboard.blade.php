<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <x-page-header title="Doctor Dashboard" :subtitle="'Welcome back, '.auth()->user()->name.'. Today is '.today()->format('l, d M Y').'.'" />

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <x-stat-card label="Today" :value="$stats['today']" hint="appointments" :href="route('doctor.schedule.index')" />
            <x-stat-card label="Upcoming" :value="$stats['upcoming']" hint="booked appointments" :href="route('doctor.appointments.index', ['status' => 'booked'])" />
            <x-stat-card label="Awaiting record" :value="$stats['awaiting']" hint="visits without a record" :href="route('doctor.appointments.index')" />
            <x-stat-card label="Records" :value="$stats['records']" hint="written by you" :href="route('doctor.records.index')" />
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <x-card title="Today's appointments" :padding="false">
                <x-table empty="No appointments today.">
                    @if ($todays->isNotEmpty())
                        <x-slot name="head">
                            <tr>
                                <th>Time</th>
                                <th>Patient</th>
                                <th>Service</th>
                                <th>Status</th>
                            </tr>
                        </x-slot>
                    @endif

                    @foreach ($todays as $appointment)
                        <tr>
                            <td>{{ $appointment->slot->start_time_label }}</td>
                            <td>
                                <a href="{{ route('doctor.appointments.edit', $appointment) }}" class="font-medium">{{ $appointment->patient->name }}</a>
                            </td>
                            <td>{{ $appointment->service->name }}</td>
                            <td><x-badge :status="$appointment->status->value" /></td>
                        </tr>
                    @endforeach
                </x-table>
            </x-card>

            <x-card title="Needs a medical record" :padding="false">
                <x-table empty="You are all caught up. No visits are waiting for a record.">
                    @if ($awaitingRecord->isNotEmpty())
                        <x-slot name="head">
                            <tr>
                                <th>Date</th>
                                <th>Patient</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </x-slot>
                    @endif

                    @foreach ($awaitingRecord as $appointment)
                        <tr>
                            <td>{{ $appointment->slot->slot_date->format('d M Y') }}, {{ $appointment->slot->start_time_label }}</td>
                            <td>{{ $appointment->patient->name }}</td>
                            <td class="text-right">
                                <x-button :href="route('doctor.records.create', ['appointment' => $appointment->id])" size="sm">Write record</x-button>
                            </td>
                        </tr>
                    @endforeach
                </x-table>
            </x-card>
        </div>
    </div>
</x-app-layout>
