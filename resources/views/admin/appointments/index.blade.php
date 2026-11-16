<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <x-page-header title="Appointments" subtitle="All appointments across the clinic (view only)." />

        <x-card>
            <form method="GET" class="space-y-4">
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <x-field label="Search" name="search" class="sm:col-span-2 lg:col-span-1">
                        <x-text-input name="search" type="search" class="block w-full" placeholder="Appointment ID or patient" :value="request('search')" />
                    </x-field>

                    <x-field label="Doctor" name="doctor">
                        <x-select name="doctor" class="block w-full">
                            <option value="">All doctors</option>
                            @foreach ($doctors as $doctor)
                                <option value="{{ $doctor->id }}" @selected(request('doctor') == $doctor->id)>{{ $doctor->name }}</option>
                            @endforeach
                        </x-select>
                    </x-field>

                    <x-field label="Service" name="service">
                        <x-select name="service" class="block w-full">
                            <option value="">All services</option>
                            @foreach ($services as $service)
                                <option value="{{ $service->id }}" @selected(request('service') == $service->id)>{{ $service->name }}</option>
                            @endforeach
                        </x-select>
                    </x-field>

                    <x-field label="Status" name="status">
                        <x-select name="status" class="block w-full">
                            <option value="">All statuses</option>
                            @foreach (\App\Enums\AppointmentStatus::cases() as $status)
                                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                            @endforeach
                        </x-select>
                    </x-field>

                    <x-field label="From date" name="from">
                        <x-text-input name="from" type="date" class="block w-full" :value="request('from')" />
                    </x-field>

                    <x-field label="To date" name="to">
                        <x-text-input name="to" type="date" class="block w-full" :value="request('to')" />
                    </x-field>
                </div>

                <div class="flex gap-2">
                    <x-button type="submit">Apply filters</x-button>
                    @if ($filtered)
                        <x-button :href="route('admin.appointments.index')" variant="ghost">Clear</x-button>
                    @endif
                </div>
            </form>
        </x-card>

        <x-card :padding="false">
            <x-table :empty="$filtered ? 'No appointments match your filters.' : 'No appointments have been booked yet.'">
                @if ($appointments->isNotEmpty())
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

                @foreach ($appointments as $appointment)
                    <tr>
                        <td>{{ $appointment->appointment_code }}</td>
                        <td>{{ $appointment->slot->slot_date->format('d M Y') }}, {{ $appointment->slot->start_time_label }}</td>
                        <td>
                            <a href="{{ route('admin.patients.show', $appointment->patient) }}" class="font-medium">{{ $appointment->patient->name }}</a>
                        </td>
                        <td>{{ $appointment->slot->doctor->name }}</td>
                        <td>{{ $appointment->service->name }}</td>
                        <td><x-badge :status="$appointment->status->value" /></td>
                    </tr>
                @endforeach
            </x-table>
        </x-card>

        {{ $appointments->links() }}
    </div>
</x-app-layout>
