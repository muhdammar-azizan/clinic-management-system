<x-app-layout>
    @php
        $field = fn ($value) => filled($value) ? $value : '-';
    @endphp

    <div class="mx-auto max-w-4xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <x-page-header :title="'Medical Record '.$record->record_code" :subtitle="'Visit on '.$record->visit_date->format('d M Y')">
            <x-slot name="actions">
                <x-button :href="route('patient.records.index')" variant="ghost">Back</x-button>
            </x-slot>
        </x-page-header>

        <x-card title="Visit details">
            <dl class="grid gap-4 text-sm sm:grid-cols-2">
                <div><dt class="text-muted">Doctor</dt><dd class="font-medium text-ink">{{ $record->doctor->name }}</dd></div>
                <div><dt class="text-muted">Service</dt><dd class="font-medium text-ink">{{ $field($record->doctor->doctorProfile?->service->name) }}</dd></div>
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

        <x-card title="Prescription files" :padding="false">
            @if ($prescriptions->isEmpty())
                <p class="px-5 py-4 text-sm text-muted">No prescription files were attached to this visit.</p>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($prescriptions as $file)
                        <li class="flex flex-wrap items-center justify-between gap-2 px-5 py-3 text-sm">
                            <span class="min-w-0">
                                <span class="font-medium text-ink">{{ $file->original_name }}</span>
                                <span class="ms-2 text-xs text-muted">{{ number_format($file->size / 1024, 0) }} KB</span>
                            </span>
                            <x-button :href="route('patient.records.attachments.show', $file)" variant="secondary" size="sm">Download</x-button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>
</x-app-layout>
