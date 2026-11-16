<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <x-page-header title="Doctors" subtitle="Create and manage doctor accounts.">
            <x-slot name="actions">
                <x-button :href="route('admin.doctors.create')">Add Doctor</x-button>
            </x-slot>
        </x-page-header>

        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif
        @if (session('error'))
            <x-alert variant="danger">{{ session('error') }}</x-alert>
        @endif

        <x-card>
            <form method="GET" class="grid gap-3 sm:grid-cols-[1fr_14rem_auto]">
                <x-text-input name="search" type="search" class="block w-full" placeholder="Search by name or email" :value="request('search')" />
                <x-select name="service" class="block w-full">
                    <option value="">All services</option>
                    @foreach ($services as $service)
                        <option value="{{ $service->id }}" @selected(request('service') == $service->id)>{{ $service->name }}</option>
                    @endforeach
                </x-select>
                <div class="flex gap-2">
                    <x-button type="submit" variant="secondary">Filter</x-button>
                    @if (request()->hasAny(['search', 'service']))
                        <x-button :href="route('admin.doctors.index')" variant="ghost">Clear</x-button>
                    @endif
                </div>
            </form>
        </x-card>

        <x-card :padding="false">
            <x-table empty="No doctors found.">
                @if ($doctors->isNotEmpty())
                    <x-slot name="head">
                        <tr>
                            <th>Name</th>
                            <th>Service</th>
                            <th>License</th>
                            <th>Phone</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </x-slot>
                @endif

                @foreach ($doctors as $doctor)
                    <tr>
                        <td>
                            <div class="font-medium text-ink">{{ $doctor->name }}</div>
                            <div class="text-xs text-muted">{{ $doctor->email }}</div>
                        </td>
                        <td>{{ $doctor->doctorProfile?->service->name ?? '-' }}</td>
                        <td>{{ $doctor->doctorProfile?->license_number ?? '-' }}</td>
                        <td>{{ $doctor->phone ?? '-' }}</td>
                        <td class="text-right">
                            <div class="flex justify-end gap-2">
                                <x-button :href="route('admin.doctors.edit', $doctor)" variant="secondary" size="sm">Edit</x-button>
                                <x-button variant="danger" size="sm"
                                          @click="$dispatch('confirm-delete', { action: '{{ route('admin.doctors.destroy', $doctor) }}' })">Delete</x-button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-table>
        </x-card>

        {{ $doctors->links() }}
    </div>

    <x-confirm-modal />
</x-app-layout>
