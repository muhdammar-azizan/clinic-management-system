<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <x-page-header title="Patients" subtitle="View and manage registered patients." />

        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif

        <x-card>
            <form method="GET" class="grid gap-3 sm:grid-cols-[1fr_auto]">
                <x-text-input name="search" type="search" class="block w-full" placeholder="Search by name, email or phone" :value="request('search')" />
                <div class="flex gap-2">
                    <x-button type="submit" variant="secondary">Search</x-button>
                    @if (request()->filled('search'))
                        <x-button :href="route('admin.patients.index')" variant="ghost">Clear</x-button>
                    @endif
                </div>
            </form>
        </x-card>

        <x-card :padding="false">
            <x-table empty="No patients found.">
                @if ($patients->isNotEmpty())
                    <x-slot name="head">
                        <tr>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Appointments</th>
                            <th>Registered</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </x-slot>
                @endif

                @foreach ($patients as $patient)
                    <tr>
                        <td>
                            <a href="{{ route('admin.patients.show', $patient) }}" class="font-medium">{{ $patient->name }}</a>
                            <div class="text-xs text-muted">{{ $patient->email }}</div>
                        </td>
                        <td>{{ $patient->phone ?? '-' }}</td>
                        <td>{{ $patient->appointments_count }}</td>
                        <td>{{ $patient->created_at->format('d M Y') }}</td>
                        <td class="text-right">
                            <div class="flex justify-end gap-2">
                                <x-button :href="route('admin.patients.show', $patient)" variant="secondary" size="sm">View</x-button>
                                <x-button :href="route('admin.patients.edit', $patient)" variant="secondary" size="sm">Edit</x-button>
                                <x-button variant="danger" size="sm"
                                          @click="$dispatch('confirm-delete', { action: '{{ route('admin.patients.destroy', $patient) }}' })">Delete</x-button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-table>
        </x-card>

        {{ $patients->links() }}
    </div>

    <x-confirm-modal message="This also deletes the patient's appointments and medical records. This action cannot be undone." />
</x-app-layout>
