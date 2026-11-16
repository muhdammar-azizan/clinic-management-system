<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <x-page-header title="Medical Records" subtitle="Records you have written. Create a new one from an appointment." />

        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif

        <x-card>
            <form method="GET" class="space-y-4">
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <x-field label="Search" name="search" class="sm:col-span-2 lg:col-span-1">
                        <x-text-input name="search" type="search" class="block w-full" placeholder="Patient, diagnosis or record ID" :value="request('search')" />
                    </x-field>

                    <x-field label="Condition" name="condition">
                        <x-select name="condition" class="block w-full">
                            <option value="">All conditions</option>
                            @foreach (\App\Enums\ConditionStatus::cases() as $condition)
                                <option value="{{ $condition->value }}" @selected(request('condition') === $condition->value)>{{ $condition->label() }}</option>
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
                        <x-button :href="route('doctor.records.index')" variant="ghost">Clear</x-button>
                    @endif
                </div>
            </form>
        </x-card>

        <x-card :padding="false">
            <x-table :empty="$filtered ? 'No records match your filters.' : 'You have not written any medical records yet. Open an appointment to write one.'">
                @if ($records->isNotEmpty())
                    <x-slot name="head">
                        <tr>
                            <th>ID</th>
                            <th>Visit date</th>
                            <th>Patient</th>
                            <th>Diagnosis</th>
                            <th>Condition</th>
                            <th>Files</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </x-slot>
                @endif

                @foreach ($records as $record)
                    <tr>
                        <td><a href="{{ route('doctor.records.show', $record) }}" class="font-medium">{{ $record->record_code }}</a></td>
                        <td>{{ $record->visit_date->format('d M Y') }}</td>
                        <td>{{ $record->patient->name }}</td>
                        <td class="max-w-xs truncate">{{ $record->diagnosis }}</td>
                        <td><x-badge :status="$record->condition->value" /></td>
                        <td>{{ $record->attachments_count }}</td>
                        <td class="text-right">
                            <div class="flex justify-end gap-2">
                                <x-button :href="route('doctor.records.show', $record)" variant="secondary" size="sm">View</x-button>
                                <x-button :href="route('doctor.records.edit', $record)" variant="secondary" size="sm">Edit</x-button>
                                <x-button variant="danger" size="sm"
                                          @click="$dispatch('confirm-delete', { action: '{{ route('doctor.records.destroy', $record) }}' })">Delete</x-button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-table>
        </x-card>

        {{ $records->links() }}
    </div>

    <x-confirm-modal message="The record will be removed from your list." />
</x-app-layout>
