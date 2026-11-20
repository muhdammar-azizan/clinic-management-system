@php($editing = $slot->exists)

<form method="POST" action="{{ $editing ? route('admin.rosters.update', $slot) : route('admin.rosters.store') }}" class="space-y-6">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    <x-card title="Slot details">
        <div class="grid gap-4 sm:grid-cols-2">
            @if ($editing)
                <x-field label="Slot ID">
                    <x-text-input class="block w-full" :value="$slot->slot_code" disabled />
                </x-field>
            @endif

            <x-field label="Doctor" name="doctor_id" required>
                <x-select id="doctor_id" name="doctor_id" class="block w-full" required>
                    <option value="">Select a doctor</option>
                    @foreach ($doctors as $doctor)
                        <option value="{{ $doctor->id }}" @selected(old('doctor_id', $slot->doctor_id) == $doctor->id)>{{ $doctor->name }}</option>
                    @endforeach
                </x-select>
            </x-field>

            <x-field label="Date" name="slot_date" required>
                <x-text-input id="slot_date" name="slot_date" type="date" class="block w-full"
                              :value="old('slot_date', $slot->slot_date?->toDateString())"
                              :min="$editing ? null : today()->toDateString()" required />
            </x-field>

            <x-field label="Start time" name="start_time" required>
                <x-text-input id="start_time" name="start_time" type="time" class="block w-full"
                              :value="old('start_time', $slot->start_time_label)" required />
            </x-field>

            <x-field label="End time" name="end_time" required>
                <x-text-input id="end_time" name="end_time" type="time" class="block w-full"
                              :value="old('end_time', $slot->end_time_label)" required />
            </x-field>

            <x-field label="Status" name="status" required>
                <x-select id="status" name="status" class="block w-full" required>
                    @foreach (\App\Enums\SlotStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(old('status', $slot->status?->value ?? 'available') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </x-select>
            </x-field>

            <x-field label="Notes" name="notes" class="sm:col-span-2">
                <x-textarea id="notes" name="notes" rows="3" class="block w-full">{{ old('notes', $slot->notes) }}</x-textarea>
            </x-field>
        </div>
    </x-card>

    <div class="flex justify-end gap-3">
        <x-button :href="route('admin.rosters.index')" variant="secondary">Cancel</x-button>
        <x-button type="submit">{{ $editing ? 'Save changes' : 'Create slot' }}</x-button>
    </div>
</form>
