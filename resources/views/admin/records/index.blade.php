<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <x-page-header title="Medical Records" subtitle="Overview of records written at the clinic (view only)." />

        <x-alert variant="info">For patient privacy, diagnosis, prescriptions and attached files are visible only to the treating doctor and the patient.</x-alert>

        <x-card>
            <form method="GET" class="space-y-4">
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <x-field label="Search" name="search" class="sm:col-span-2 lg:col-span-1">
                        <x-text-input name="search" type="search" class="block w-full" placeholder="Record ID, patient or doctor" :value="request('search')" />
                    </x-field>

                    <x-field label="Doctor" name="doctor">
                        <x-select name="doctor" class="block w-full">
                            <option value="">All doctors</option>
                            @foreach ($doctors as $doctor)
                                <option value="{{ $doctor->id }}" @selected(request('doctor') == $doctor->id)>{{ $doctor->name }}</option>
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
                        <x-button :href="route('admin.records.index')" variant="ghost">Clear</x-button>
                    @endif
                </div>
            </form>
        </x-card>

        <x-card :padding="false">
            <x-table :empty="$filtered ? 'No records match your filters.' : 'No medical records have been written yet.'">
                @if ($records->isNotEmpty())
                    <x-slot name="head">
                        <tr>
                            <th>ID</th>
                            <th>Visit date</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Service</th>
                            <th>Files</th>
                        </tr>
                    </x-slot>
                @endif

                @foreach ($records as $record)
                    <tr>
                        <td>{{ $record->record_code }}</td>
                        <td>{{ $record->visit_date->format('d M Y') }}</td>
                        <td><a href="{{ route('admin.patients.show', $record->patient) }}" class="font-medium">{{ $record->patient->name }}</a></td>
                        <td>{{ $record->doctor->name }}</td>
                        <td>{{ $record->appointment?->service->name ?? '-' }}</td>
                        <td>{{ $record->attachments_count }}</td>
                    </tr>
                @endforeach
            </x-table>
        </x-card>

        {{ $records->links() }}
    </div>
</x-app-layout>
