<x-app-layout>
    <div class="mx-auto max-w-3xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <x-page-header title="Generate Slots" subtitle="Create many slots at once from a working pattern." />

        @if (session('error'))
            <x-alert variant="danger">{{ session('error') }}</x-alert>
        @endif

        <form method="POST" action="{{ route('admin.rosters.generate.store') }}" class="space-y-6">
            @csrf

            <x-card title="Doctor and dates">
                <div class="grid gap-4 sm:grid-cols-3">
                    <x-field label="Doctor" name="doctor_id" required class="sm:col-span-3">
                        <x-select id="doctor_id" name="doctor_id" class="block w-full" required>
                            <option value="">Select a doctor</option>
                            @foreach ($doctors as $doctor)
                                <option value="{{ $doctor->id }}" @selected(old('doctor_id') == $doctor->id)>{{ $doctor->name }}</option>
                            @endforeach
                        </x-select>
                    </x-field>

                    <x-field label="From date" name="from" required>
                        <x-text-input id="from" name="from" type="date" class="block w-full" :value="old('from', today()->toDateString())" :min="today()->toDateString()" required />
                    </x-field>

                    <x-field label="To date" name="to" required hint="Up to {{ \App\Services\RosterGenerator::MAX_DAYS }} days.">
                        <x-text-input id="to" name="to" type="date" class="block w-full" :value="old('to', today()->addDays(13)->toDateString())" :min="today()->toDateString()" required />
                    </x-field>
                </div>

                <div class="mt-4">
                    <p class="text-sm font-medium text-ink">Working days <span class="text-danger">*</span></p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($weekdays as $number => $label)
                            <label class="cursor-pointer">
                                <input type="checkbox" name="days[]" value="{{ $number }}" class="peer sr-only"
                                       @checked(in_array($number, (array) old('days', [1, 2, 3, 4, 5])))>
                                <span class="block rounded-card border border-line bg-white px-3 py-1.5 text-sm font-medium text-ink transition peer-checked:border-primary peer-checked:bg-primary-soft peer-focus-visible:ring-2 peer-focus-visible:ring-primary">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('days')" class="mt-2" />
                </div>
            </x-card>

            <x-card title="Working hours">
                <div class="grid gap-4 sm:grid-cols-3">
                    <x-field label="Starts" name="work_start" required>
                        <x-text-input id="work_start" name="work_start" type="time" class="block w-full" :value="old('work_start', '09:00')" required />
                    </x-field>

                    <x-field label="Ends" name="work_end" required>
                        <x-text-input id="work_end" name="work_end" type="time" class="block w-full" :value="old('work_end', '17:00')" required />
                    </x-field>

                    <x-field label="Session length" name="duration" required>
                        <x-select id="duration" name="duration" class="block w-full" required>
                            @foreach ($durations as $minutes)
                                <option value="{{ $minutes }}" @selected((int) old('duration', 30) === $minutes)>{{ $minutes }} minutes</option>
                            @endforeach
                        </x-select>
                    </x-field>

                    <x-field label="Break starts" name="break_start" hint="Optional, for example lunch.">
                        <x-text-input id="break_start" name="break_start" type="time" class="block w-full" :value="old('break_start', '13:00')" />
                    </x-field>

                    <x-field label="Break ends" name="break_end">
                        <x-text-input id="break_end" name="break_end" type="time" class="block w-full" :value="old('break_end', '14:00')" />
                    </x-field>

                    <x-field label="Notes" name="notes" class="sm:col-span-3" hint="Added to every slot, for example a room number.">
                        <x-text-input id="notes" name="notes" class="block w-full" maxlength="500" :value="old('notes')" />
                    </x-field>
                </div>
            </x-card>

            <p class="text-sm text-muted">Sessions in the past, and sessions that overlap a slot the doctor already has, are skipped. Nothing existing is changed.</p>

            <div class="flex justify-end gap-3">
                <x-button :href="route('admin.rosters.index')" variant="secondary">Cancel</x-button>
                <x-button type="submit">Generate slots</x-button>
            </div>
        </form>
    </div>
</x-app-layout>
