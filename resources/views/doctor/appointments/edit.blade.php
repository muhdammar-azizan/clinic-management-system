<x-app-layout>
    <div class="mx-auto max-w-3xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <x-page-header :title="'Appointment '.$appointment->appointment_code" :subtitle="$appointment->patient->name">
            <x-slot name="actions">
                <x-button :href="route('doctor.appointments.index')" variant="ghost">Back</x-button>
            </x-slot>
        </x-page-header>

        @if (session('error'))
            <x-alert variant="danger">{{ session('error') }}</x-alert>
        @endif

        <x-card title="Details">
            <dl class="grid gap-4 text-sm sm:grid-cols-2">
                <div><dt class="text-muted">Patient</dt><dd class="font-medium text-ink">{{ $appointment->patient->name }}</dd></div>
                <div><dt class="text-muted">Contact</dt><dd class="font-medium text-ink">{{ $appointment->patient->phone ?? $appointment->patient->email }}</dd></div>
                <div><dt class="text-muted">Date and time</dt><dd class="font-medium text-ink">{{ $appointment->slot->slot_date->format('d M Y') }}, {{ $appointment->slot->start_time_label }}</dd></div>
                <div><dt class="text-muted">Service</dt><dd class="font-medium text-ink">{{ $appointment->service->name }}</dd></div>
                <div><dt class="text-muted">Status</dt><dd><x-badge :status="$appointment->status->value" /></dd></div>
                <div class="sm:col-span-2"><dt class="text-muted">Reason for visit</dt><dd class="font-medium text-ink">{{ $appointment->reason ?: 'Not provided' }}</dd></div>
            </dl>
        </x-card>

        @if ($appointment->isBooked())
            <form method="POST" action="{{ route('doctor.appointments.update', $appointment) }}">
                @csrf
                @method('PUT')

                <x-card title="Update status">
                    <x-field label="New status" name="status" required hint="Cancelling releases the slot for other patients. Completing is only possible after the appointment has started.">
                        <x-select id="status" name="status" class="block w-full sm:w-64" required>
                            <option value="completed" @selected(old('status') === 'completed')>Completed</option>
                            <option value="cancelled" @selected(old('status') === 'cancelled')>Cancelled</option>
                        </x-select>
                    </x-field>

                    <x-slot name="footer">
                        <div class="flex justify-end">
                            <x-button type="submit">Save status</x-button>
                        </div>
                    </x-slot>
                </x-card>
            </form>
        @else
            <x-alert variant="info">This appointment is {{ $appointment->status->value }} and can no longer be changed.</x-alert>
        @endif
    </div>
</x-app-layout>
