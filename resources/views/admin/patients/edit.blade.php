<x-app-layout>
    <div class="mx-auto max-w-3xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <x-page-header title="Edit Patient" :subtitle="$patient->name" />

        <form method="POST" action="{{ route('admin.patients.update', $patient) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <x-card title="Patient details">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="Full name" name="name" required>
                        <x-text-input id="name" name="name" class="block w-full" :value="old('name', $patient->name)" required autofocus />
                    </x-field>

                    <x-field label="Email" name="email" required>
                        <x-text-input id="email" name="email" type="email" class="block w-full" :value="old('email', $patient->email)" required />
                    </x-field>

                    <x-field label="Phone" name="phone">
                        <x-text-input id="phone" name="phone" type="tel" class="block w-full" :value="old('phone', $patient->phone)" />
                    </x-field>

                    <x-field label="Date of birth" name="date_of_birth">
                        <x-text-input id="date_of_birth" name="date_of_birth" type="date" class="block w-full" :value="old('date_of_birth', $patient->date_of_birth?->toDateString())" />
                    </x-field>

                    <x-field label="Gender" name="gender">
                        <x-select id="gender" name="gender" class="block w-full">
                            <option value="">Not set</option>
                            <option value="male" @selected(old('gender', $patient->gender) === 'male')>Male</option>
                            <option value="female" @selected(old('gender', $patient->gender) === 'female')>Female</option>
                        </x-select>
                    </x-field>

                    <x-field label="Address" name="address">
                        <x-text-input id="address" name="address" class="block w-full" :value="old('address', $patient->address)" />
                    </x-field>
                </div>
            </x-card>

            <div class="flex justify-end gap-3">
                <x-button :href="route('admin.patients.show', $patient)" variant="secondary">Cancel</x-button>
                <x-button type="submit">Save changes</x-button>
            </div>
        </form>
    </div>
</x-app-layout>
