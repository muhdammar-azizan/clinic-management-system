<x-app-layout>
    @php
        $field = fn ($value) => filled($value) ? $value : '-';
    @endphp

    <div class="mx-auto max-w-4xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <x-page-header :title="'Medical Record '.$record->record_code" :subtitle="$record->patient->name">
            <x-slot name="actions">
                <x-button :href="route('doctor.records.index')" variant="ghost">Back</x-button>
                <x-button :href="route('doctor.records.edit', $record)" variant="secondary">Edit</x-button>
                <x-button variant="danger"
                          @click="$dispatch('confirm-delete', { action: '{{ route('doctor.records.destroy', $record) }}' })">Delete</x-button>
            </x-slot>
        </x-page-header>

        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif

        <x-card title="Visit details">
            <dl class="grid gap-4 text-sm sm:grid-cols-2">
                <div><dt class="text-muted">Patient</dt><dd class="font-medium text-ink">{{ $record->patient->name }}</dd></div>
                <div><dt class="text-muted">Visit date</dt><dd class="font-medium text-ink">{{ $record->visit_date->format('d M Y') }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-muted">Chief complaint</dt><dd class="font-medium text-ink">{{ $record->chief_complaint }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-muted">Symptoms</dt><dd class="whitespace-pre-line font-medium text-ink">{{ $field($record->symptoms) }}</dd></div>
            </dl>
        </x-card>

        <x-card title="Clinical assessment">
            <dl class="grid gap-4 text-sm sm:grid-cols-2">
                <div class="sm:col-span-2"><dt class="text-muted">Diagnosis</dt><dd class="whitespace-pre-line font-medium text-ink">{{ $record->diagnosis }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-muted">Treatment plan</dt><dd class="whitespace-pre-line font-medium text-ink">{{ $field($record->treatment_plan) }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-muted">Prescription</dt><dd class="whitespace-pre-line font-medium text-ink">{{ $field($record->prescription) }}</dd></div>
                <div><dt class="text-muted">Condition</dt><dd><x-badge :status="$record->condition->value" /></dd></div>
                <div><dt class="text-muted">Follow-up date</dt><dd class="font-medium text-ink">{{ $record->follow_up_date?->format('d M Y') ?? '-' }}</dd></div>
            </dl>
        </x-card>

        <x-card title="Attachments" :padding="false">
            @if ($record->attachments->isEmpty())
                <p class="px-5 py-4 text-sm text-muted">No files attached.</p>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($record->attachments as $attachment)
                        <li class="flex flex-wrap items-center justify-between gap-2 px-5 py-3 text-sm">
                            <span class="min-w-0">
                                <span class="font-medium text-ink">{{ $attachment->original_name }}</span>
                                <span class="ms-2 text-xs text-muted">{{ number_format($attachment->size / 1024, 0) }} KB</span>
                            </span>
                            <span class="flex items-center gap-3">
                                <x-badge :label="$attachment->isPrescription() ? 'Prescription' : 'Attachment'" :variant="$attachment->isPrescription() ? 'info' : 'neutral'" />
                                <x-button :href="route('doctor.records.attachments.show', $attachment)" variant="secondary" size="sm">Download</x-button>
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>

    <x-confirm-modal message="The record will be removed from your list." />
</x-app-layout>
