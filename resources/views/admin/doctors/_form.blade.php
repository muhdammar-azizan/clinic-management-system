@php($editing = $doctor->exists)

<form method="POST" action="{{ $editing ? route('admin.doctors.update', $doctor) : route('admin.doctors.store') }}" class="space-y-6">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    <x-card title="Account">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-field label="Full name" name="name" required>
                <x-text-input id="name" name="name" class="block w-full" :value="old('name', $doctor->name)" required autofocus />
            </x-field>

            <x-field label="Email" name="email" required>
                <x-text-input id="email" name="email" type="email" class="block w-full" :value="old('email', $doctor->email)" required />
            </x-field>

            <x-field label="Password" name="password" :required="! $editing" :hint="$editing ? 'Leave blank to keep the current password.' : null">
                <x-text-input id="password" name="password" type="password" class="block w-full" autocomplete="new-password" />
            </x-field>

            <x-field label="Confirm password" name="password_confirmation" :required="! $editing">
                <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="block w-full" autocomplete="new-password" />
            </x-field>
        </div>
    </x-card>

    <x-card title="Professional details">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-field label="Service" name="service_id" required>
                <x-select id="service_id" name="service_id" class="block w-full" required>
                    <option value="">Select a service</option>
                    @foreach ($services as $service)
                        <option value="{{ $service->id }}" @selected(old('service_id', $doctor->doctorProfile?->service_id) == $service->id)>{{ $service->name }}</option>
                    @endforeach
                </x-select>
            </x-field>

            <x-field label="License number" name="license_number">
                <x-text-input id="license_number" name="license_number" class="block w-full" :value="old('license_number', $doctor->doctorProfile?->license_number)" />
            </x-field>

            <x-field label="Phone" name="phone">
                <x-text-input id="phone" name="phone" type="tel" class="block w-full" :value="old('phone', $doctor->phone)" />
            </x-field>

            <x-field label="Bio" name="bio" class="sm:col-span-2">
                <x-textarea id="bio" name="bio" rows="3" class="block w-full">{{ old('bio', $doctor->doctorProfile?->bio) }}</x-textarea>
            </x-field>
        </div>
    </x-card>

    <div class="flex justify-end gap-3">
        <x-button :href="route('admin.doctors.index')" variant="secondary">Cancel</x-button>
        <x-button type="submit">{{ $editing ? 'Save changes' : 'Create doctor' }}</x-button>
    </div>
</form>
