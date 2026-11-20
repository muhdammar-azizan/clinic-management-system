<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <x-page-header title="Doctor Roster" subtitle="Create and manage appointment slots for each doctor.">
            <x-slot name="actions">
                @include('admin.rosters._tabs')
                <x-button :href="route('admin.rosters.create')">Add Slot</x-button>
            </x-slot>
        </x-page-header>

        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif
        @if (session('error'))
            <x-alert variant="danger">{{ session('error') }}</x-alert>
        @endif

        <x-card>
            {{-- Filters apply by themselves: typing waits 400 ms, dropdowns and dates apply at once. --}}
            <form method="GET" class="space-y-4" x-data x-ref="filters"
                  @input.debounce.400ms="if ($event.target.type === 'search') $refs.filters.requestSubmit()"
                  @change="if ($event.target.type !== 'search') $refs.filters.requestSubmit()">
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <x-field label="Search" name="search" class="sm:col-span-2 lg:col-span-1">
                        <x-text-input name="search" type="search" class="block w-full" placeholder="Slot ID, doctor or notes" :value="request('search')"
                                      x-init="if ($el.value) { $el.focus(); $el.setSelectionRange($el.value.length, $el.value.length) }" />
                    </x-field>

                    <x-field label="Doctor" name="doctor">
                        <x-select name="doctor" class="block w-full">
                            <option value="">All doctors</option>
                            @foreach ($doctors as $doctor)
                                <option value="{{ $doctor->id }}" @selected(request('doctor') == $doctor->id)>{{ $doctor->name }}</option>
                            @endforeach
                        </x-select>
                    </x-field>

                    <x-field label="Service" name="service">
                        <x-select name="service" class="block w-full">
                            <option value="">All services</option>
                            @foreach ($services as $service)
                                <option value="{{ $service->id }}" @selected(request('service') == $service->id)>{{ $service->name }}</option>
                            @endforeach
                        </x-select>
                    </x-field>

                    <x-field label="Sort by" name="sort">
                        <x-select name="sort" class="block w-full">
                            @foreach ($sortOptions as $value => $label)
                                <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                            @endforeach
                        </x-select>
                    </x-field>

                    <x-field label="Status" name="status">
                        <x-select name="status" class="block w-full">
                            <option value="">All statuses</option>
                            @foreach (\App\Enums\SlotStatus::cases() as $status)
                                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                            @endforeach
                        </x-select>
                    </x-field>

                    <x-field label="From date" name="from">
                        <x-text-input name="from" type="date" class="block w-full" :value="request('from')" />
                    </x-field>

                    <x-field label="To date" name="to">
                        <x-text-input name="to" type="date" class="block w-full" :value="request('to')" />
                    </x-field>
                </div>

                <div class="flex gap-2">
                    <x-button type="submit">Apply</x-button>
                    @if ($filtered)
                        <x-button :href="route('admin.rosters.index')" variant="ghost">Clear</x-button>
                    @endif
                </div>
            </form>
        </x-card>

        <x-card :padding="false">
            <x-table :empty="$filtered ? 'No slots match your filters.' : 'No slots yet. Add the first slot to start taking bookings.'">
                @if ($slots->isNotEmpty())
                    <x-slot name="head">
                        <tr>
                            <th>Slot ID</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Doctor</th>
                            <th>Status</th>
                            <th>Notes</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </x-slot>
                @endif

                @foreach ($slots as $slot)
                    <tr>
                        <td>{{ $slot->slot_code }}</td>
                        <td>{{ $slot->slot_date->format('d M Y') }}</td>
                        <td>{{ $slot->start_time_label }}</td>
                        <td>
                            <div class="font-medium text-ink">{{ $slot->doctor->name }}</div>
                            <div class="text-xs text-muted">{{ $slot->doctor->doctorProfile?->service->name }}</div>
                        </td>
                        <td><x-badge :status="$slot->status->value" /></td>
                        <td class="max-w-xs truncate">{{ $slot->notes ?? '-' }}</td>
                        <td class="text-right">
                            @if ($slot->active_appointment_exists)
                                <span class="text-xs text-muted">Booked</span>
                            @else
                                <div class="flex justify-end gap-2">
                                    <x-button :href="route('admin.rosters.edit', $slot)" variant="secondary" size="sm">Edit</x-button>
                                    <x-button variant="danger" size="sm"
                                              @click="$dispatch('confirm-delete', { action: '{{ route('admin.rosters.destroy', $slot) }}' })">Delete</x-button>
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-table>
        </x-card>

        {{ $slots->links() }}
    </div>

    <x-confirm-modal />
</x-app-layout>
