<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <x-page-header title="Appointments" subtitle="Appointments booked on your schedule." />

        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif
        @if (session('error'))
            <x-alert variant="danger">{{ session('error') }}</x-alert>
        @endif

        <x-card>
            <form method="GET" class="grid gap-3 sm:grid-cols-[1fr_12rem_auto]">
                <x-text-input name="search" type="search" class="block w-full" placeholder="Search by patient name or email" :value="request('search')" />
                <x-select name="status" class="block w-full">
                    <option value="">All statuses</option>
                    @foreach (\App\Enums\AppointmentStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </x-select>
                <div class="flex gap-2">
                    <x-button type="submit" variant="secondary">Filter</x-button>
                    @if ($filtered)
                        <x-button :href="route('doctor.appointments.index')" variant="ghost">Clear</x-button>
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
                            <th>Service</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </x-slot>
                @endif

                @foreach ($appointments as $appointment)
                    <tr>
                        <td>{{ $appointment->appointment_code }}</td>
                        <td>{{ $appointment->slot->slot_date->format('d M Y') }}, {{ $appointment->slot->start_time_label }}</td>
                        <td>
                            <div class="font-medium text-ink">{{ $appointment->patient->name }}</div>
                            <div class="text-xs text-muted">{{ $appointment->patient->phone ?? $appointment->patient->email }}</div>
                        </td>
                        <td>{{ $appointment->service->name }}</td>
                        <td><x-badge :status="$appointment->status->value" /></td>
                        <td class="text-right">
                            <div class="flex justify-end gap-2">
                                <x-button :href="route('doctor.appointments.edit', $appointment)" variant="secondary" size="sm">{{ $appointment->isBooked() ? 'Manage' : 'View' }}</x-button>
                                <x-button variant="danger" size="sm"
                                          @click="$dispatch('confirm-delete', { action: '{{ route('doctor.appointments.destroy', $appointment) }}' })">Delete</x-button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-table>
        </x-card>

        {{ $appointments->links() }}
    </div>

    <x-confirm-modal />
</x-app-layout>
