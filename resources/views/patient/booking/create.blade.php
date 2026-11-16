<x-app-layout>
    <div class="mx-auto max-w-4xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <x-page-header title="Book an Appointment" subtitle="Choose a service, pick a time, then confirm." />

        @if (session('error'))
            <x-alert variant="danger">{{ session('error') }}</x-alert>
        @endif

        {{-- Step 1: service --}}
        <x-card title="1. Choose a service">
            <div class="grid gap-3 sm:grid-cols-2">
                @forelse ($services as $item)
                    <a href="{{ route('patient.booking.create', ['service' => $item->id]) }}"
                       @class([
                           'block rounded-card border p-4 transition',
                           'border-primary bg-primary-soft' => $service?->id === $item->id,
                           'border-line bg-white hover:border-primary' => $service?->id !== $item->id,
                       ])
                       @if ($service?->id === $item->id) aria-current="true" @endif>
                        <span class="block font-semibold text-deep">{{ $item->name }}</span>
                        @if ($item->description)
                            <span class="mt-0.5 block text-sm text-muted">{{ $item->description }}</span>
                        @endif
                    </a>
                @empty
                    <p class="text-sm text-muted sm:col-span-2">No services are available right now.</p>
                @endforelse
            </div>
        </x-card>

        @if ($service)
            <form method="POST" action="{{ route('patient.booking.store') }}" class="space-y-6">
                @csrf
                <input type="hidden" name="service_id" value="{{ $service->id }}">

                {{-- Step 2: slot --}}
                <x-card :title="'2. Pick a time for '.$service->name">
                    @forelse ($slotsByDay as $date => $slots)
                        <div class="@if (! $loop->first) mt-5 @endif">
                            <h3 class="text-sm font-semibold text-ink">{{ \Illuminate\Support\Carbon::parse($date)->format('l, d M Y') }}</h3>
                            <div class="mt-2 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($slots as $slot)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="roster_slot_id" value="{{ $slot->id }}" class="peer sr-only"
                                               @checked(old('roster_slot_id') == $slot->id) required>
                                        <span class="block rounded-card border border-line bg-white px-3 py-2 text-sm transition hover:border-primary peer-checked:border-primary peer-checked:bg-primary-soft peer-focus-visible:ring-2 peer-focus-visible:ring-primary">
                                            <span class="block font-semibold text-deep">{{ $slot->start_time_label }}</span>
                                            <span class="block truncate text-muted">{{ $slot->doctor->name }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <x-empty-state title="No available slots" description="There are no open slots for this service in the next 30 days. Please check again later." />
                    @endforelse

                    <x-input-error :messages="$errors->get('roster_slot_id')" class="mt-3" />
                </x-card>

                {{-- Step 3: confirm --}}
                @if ($slotsByDay->isNotEmpty())
                    <x-card title="3. Confirm">
                        <x-field label="Reason for visit" name="reason" hint="Optional. Share anything the doctor should know.">
                            <x-textarea id="reason" name="reason" rows="3" class="block w-full" maxlength="500">{{ old('reason') }}</x-textarea>
                        </x-field>

                        <x-slot name="footer">
                            <div class="flex justify-end">
                                <x-button type="submit" variant="accent">Confirm Booking</x-button>
                            </div>
                        </x-slot>
                    </x-card>
                @endif
            </form>
        @endif
    </div>
</x-app-layout>
