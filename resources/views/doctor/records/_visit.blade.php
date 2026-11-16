{{-- Step 1. $record is null on create. $appointment may be null if it was deleted after the record was written. --}}
<x-card title="Visit details">
    <div class="grid gap-4 sm:grid-cols-2">
        <x-field label="Visit date" name="visit_date" required>
            <x-text-input id="visit_date" name="visit_date" type="date" class="block w-full"
                          :value="old('visit_date', $record?->visit_date->toDateString() ?? $appointment?->slot->slot_date->toDateString())"
                          max="{{ today()->toDateString() }}" required />
        </x-field>

        <x-field label="Service">
            <x-text-input class="block w-full" :value="$appointment?->service->name ?? '-'" disabled />
        </x-field>

        <x-field label="Chief complaint" name="chief_complaint" required class="sm:col-span-2"
                 :hint="$appointment?->reason ? 'Patient said: '.$appointment->reason : 'The main reason for the visit.'">
            <x-text-input id="chief_complaint" name="chief_complaint" class="block w-full" maxlength="255"
                          :value="old('chief_complaint', $record?->chief_complaint ?? $appointment?->reason)" required />
        </x-field>

        <x-field label="Symptoms" name="symptoms" class="sm:col-span-2">
            <x-textarea id="symptoms" name="symptoms" rows="3" class="block w-full" maxlength="2000">{{ old('symptoms', $record?->symptoms) }}</x-textarea>
        </x-field>
    </div>
</x-card>
