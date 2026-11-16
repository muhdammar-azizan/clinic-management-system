<x-app-layout>
    <div class="mx-auto max-w-5xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <x-page-header title="My Medical Records" subtitle="Your visit history from the clinic. Records are written by your doctor and cannot be edited here." />

        <x-card :padding="false">
            <x-table empty="You have no medical records yet. Records appear here after a doctor completes your visit.">
                @if ($records->isNotEmpty())
                    <x-slot name="head">
                        <tr>
                            <th>ID</th>
                            <th>Visit date</th>
                            <th>Doctor</th>
                            <th>Diagnosis</th>
                            <th>Condition</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </x-slot>
                @endif

                @foreach ($records as $record)
                    <tr>
                        <td><a href="{{ route('patient.records.show', $record) }}" class="font-medium">{{ $record->record_code }}</a></td>
                        <td>{{ $record->visit_date->format('d M Y') }}</td>
                        <td>
                            <div class="font-medium text-ink">{{ $record->doctor->name }}</div>
                            <div class="text-xs text-muted">{{ $record->doctor->doctorProfile?->service->name }}</div>
                        </td>
                        <td class="max-w-xs truncate">{{ $record->diagnosis }}</td>
                        <td><x-badge :status="$record->condition->value" /></td>
                        <td class="text-right">
                            <x-button :href="route('patient.records.show', $record)" variant="secondary" size="sm">View</x-button>
                        </td>
                    </tr>
                @endforeach
            </x-table>
        </x-card>

        {{ $records->links() }}
    </div>
</x-app-layout>
