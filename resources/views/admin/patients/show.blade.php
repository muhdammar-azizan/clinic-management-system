<x-app-layout>
    <div class="mx-auto max-w-5xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <x-page-header :title="$patient->name" subtitle="Patient record">
            <x-slot name="actions">
                <x-button :href="route('admin.patients.index')" variant="ghost">Back</x-button>
                <x-button :href="route('admin.patients.edit', $patient)" variant="secondary">Edit</x-button>
                <x-button variant="danger"
                          @click="$dispatch('confirm-delete', { action: '{{ route('admin.patients.destroy', $patient) }}' })">Delete</x-button>
            </x-slot>
        </x-page-header>

        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif

        <x-card title="Details">
            <dl class="grid gap-4 text-sm sm:grid-cols-2">
                <div><dt class="text-muted">Email</dt><dd class="font-medium text-ink">{{ $patient->email }}</dd></div>
                <div><dt class="text-muted">Phone</dt><dd class="font-medium text-ink">{{ $patient->phone ?? '-' }}</dd></div>
                <div><dt class="text-muted">Date of birth</dt><dd class="font-medium text-ink">{{ $patient->date_of_birth?->format('d M Y') ?? '-' }}</dd></div>
                <div><dt class="text-muted">Gender</dt><dd class="font-medium text-ink">{{ $patient->gender ? ucfirst($patient->gender) : '-' }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-muted">Address</dt><dd class="font-medium text-ink">{{ $patient->address ?? '-' }}</dd></div>
                <div><dt class="text-muted">Registered</dt><dd class="font-medium text-ink">{{ $patient->created_at->format('d M Y') }}</dd></div>
                <div><dt class="text-muted">Medical records</dt><dd class="font-medium text-ink">{{ $patient->medical_records_count }}</dd></div>
            </dl>
        </x-card>

        <x-card title="Appointments" :padding="false">
            <x-table empty="This patient has no appointments yet.">
                @if ($appointments->isNotEmpty())
                    <x-slot name="head">
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
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
                        <td>{{ $appointment->doctor->name }}</td>
                        <td>{{ $appointment->service->name }}</td>
                        <td><x-badge :status="$appointment->status->value" /></td>
                    </tr>
                @endforeach
            </x-table>
        </x-card>
    </div>

    <x-confirm-modal message="This also deletes the patient's appointments and medical records. This action cannot be undone." />
</x-app-layout>
