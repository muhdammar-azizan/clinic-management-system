{{-- Step 2. $record is null on create. --}}
<x-card title="Clinical assessment">
    <div class="grid gap-4 sm:grid-cols-2">
        <x-field label="Diagnosis" name="diagnosis" required class="sm:col-span-2">
            <x-textarea id="diagnosis" name="diagnosis" rows="3" class="block w-full" maxlength="2000" required>{{ old('diagnosis', $record?->diagnosis) }}</x-textarea>
        </x-field>

        <x-field label="Treatment plan" name="treatment_plan" class="sm:col-span-2">
            <x-textarea id="treatment_plan" name="treatment_plan" rows="3" class="block w-full" maxlength="2000">{{ old('treatment_plan', $record?->treatment_plan) }}</x-textarea>
        </x-field>

        <x-field label="Prescription" name="prescription" class="sm:col-span-2" hint="Medicines and dosage. You can also attach a prescription file in the next step.">
            <x-textarea id="prescription" name="prescription" rows="3" class="block w-full" maxlength="2000">{{ old('prescription', $record?->prescription) }}</x-textarea>
        </x-field>

        <x-field label="Patient condition" name="condition" required>
            <x-select id="condition" name="condition" class="block w-full" required>
                @foreach (\App\Enums\ConditionStatus::cases() as $condition)
                    <option value="{{ $condition->value }}" @selected(old('condition', $record?->condition->value ?? 'stable') === $condition->value)>{{ $condition->label() }}</option>
                @endforeach
            </x-select>
        </x-field>

        <x-field label="Follow-up date" name="follow_up_date">
            <x-text-input id="follow_up_date" name="follow_up_date" type="date" class="block w-full"
                          :value="old('follow_up_date', $record?->follow_up_date?->toDateString())" />
        </x-field>
    </div>
</x-card>
