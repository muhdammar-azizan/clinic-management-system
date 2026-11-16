<x-app-layout>
    <div class="mx-auto max-w-3xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <x-page-header :title="'Edit '.$record->record_code" :subtitle="$record->patient->name">
            <x-slot name="actions">
                <x-button :href="route('doctor.records.show', $record)" variant="ghost">Cancel</x-button>
            </x-slot>
        </x-page-header>

        <form method="POST" action="{{ route('doctor.records.update', $record) }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            @include('doctor.records._visit')
            @include('doctor.records._assessment')
            @include('doctor.records._files')

            <div class="flex justify-end gap-3">
                <x-button :href="route('doctor.records.show', $record)" variant="secondary">Cancel</x-button>
                <x-button type="submit">Save changes</x-button>
            </div>
        </form>
    </div>
</x-app-layout>
