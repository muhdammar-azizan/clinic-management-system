<x-app-layout>
    <div class="mx-auto max-w-3xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <x-page-header title="Edit Slot" :subtitle="$slot->slot_code" />

        @include('admin.rosters._form')
    </div>
</x-app-layout>
