<x-app-layout>
    <div class="mx-auto max-w-5xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <x-page-header title="My Appointments" subtitle="Your upcoming visits and appointment history.">
            <x-slot name="actions">
                <x-button :href="route('patient.booking.create')" variant="accent">Book Appointment</x-button>
            </x-slot>
        </x-page-header>

        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif
        @if (session('error'))
            <x-alert variant="danger">{{ session('error') }}</x-alert>
        @endif

        <x-card title="Upcoming" :padding="false">
            <x-table empty="You have no upcoming appointments.">
                @if ($upcoming->isNotEmpty())
                    <x-slot name="head">
                        <tr>
                            <th>ID</th>
                            <th>Date and time</th>
                            <th>Doctor</th>
                            <th>Service</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </x-slot>
                @endif

                @foreach ($upcoming as $appointment)
                    <tr>
                        <td>{{ $appointment->appointment_code }}</td>
                        <td>{{ $appointment->slot->slot_date->format('d M Y') }}, {{ $appointment->slot->start_time_label }}</td>
                        <td>{{ $appointment->doctor->name }}</td>
                        <td>{{ $appointment->service->name }}</td>
                        <td><x-badge :status="$appointment->status->value" /></td>
                        <td class="text-right">
                            <x-button variant="danger" size="sm"
                                      @click="$dispatch('confirm-delete', { action: '{{ route('patient.appointments.cancel', $appointment) }}' })">Cancel</x-button>
                        </td>
                    </tr>
                @endforeach
            </x-table>
        </x-card>

        <x-card title="History" :padding="false">
            <x-table empty="No past or cancelled appointments yet.">
                @if ($history->isNotEmpty())
                    <x-slot name="head">
                        <tr>
                            <th>ID</th>
                            <th>Date and time</th>
                            <th>Doctor</th>
                            <th>Service</th>
                            <th>Status</th>
                        </tr>
                    </x-slot>
                @endif

                @foreach ($history as $appointment)
                    <tr>
                        <td>{{ $appointment->appointment_code }}</td>
                        <td>{{ $appointment->slot->slot_date->format('d M Y') }}, {{ $appointment->slot->start_time_label }}</td>
                        <td>{{ $appointment->doctor->name }}</td>
                        <td>{{ $appointment->service->name }}</td>
                        <td><x-badge :status="$appointment->status->value" /></td>
                    </tr>
                @endforeach
            </x-table>
        </x-card>
    </div>

    <x-confirm-modal method="PATCH"
                     title="Cancel this appointment?"
                     message="The slot will be released for other patients."
                     confirmLabel="Yes, cancel appointment" />
</x-app-layout>
