<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <x-page-header title="Doctor Roster" subtitle="Create and manage appointment slots for each doctor.">
            <x-slot name="actions">
                @include('admin.rosters._tabs')
                <x-button :href="route('admin.rosters.create')">Add Slot</x-button>
            </x-slot>
        </x-page-header>

        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif
        @if (session('error'))
            <x-alert variant="danger">{{ session('error') }}</x-alert>
        @endif

        <x-card :padding="false">
            <x-table empty="No slots yet. Add the first slot to start taking bookings.">
                @if ($slots->isNotEmpty())
                    <x-slot name="head">
                        <tr>
                            <th>Slot ID</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Doctor</th>
                            <th>Status</th>
                            <th>Notes</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </x-slot>
                @endif

                @foreach ($slots as $slot)
                    <tr>
                        <td>{{ $slot->slot_code }}</td>
                        <td>{{ $slot->slot_date->format('d M Y') }}</td>
                        <td>{{ $slot->start_time_label }}</td>
                        <td>
                            <div class="font-medium text-ink">{{ $slot->doctor->name }}</div>
                            <div class="text-xs text-muted">{{ $slot->doctor->doctorProfile?->service->name }}</div>
                        </td>
                        <td><x-badge :status="$slot->status->value" /></td>
                        <td class="max-w-xs truncate">{{ $slot->notes ?? '-' }}</td>
                        <td class="text-right">
                            @if ($slot->active_appointment_exists)
                                <span class="text-xs text-muted">Booked</span>
                            @else
                                <div class="flex justify-end gap-2">
                                    <x-button :href="route('admin.rosters.edit', $slot)" variant="secondary" size="sm">Edit</x-button>
                                    <x-button variant="danger" size="sm"
                                              @click="$dispatch('confirm-delete', { action: '{{ route('admin.rosters.destroy', $slot) }}' })">Delete</x-button>
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-table>
        </x-card>

        {{ $slots->links() }}
    </div>

    <x-confirm-modal />
</x-app-layout>
