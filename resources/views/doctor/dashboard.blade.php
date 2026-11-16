<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <x-page-header title="Doctor Dashboard" subtitle="Your day at a glance." />

        <x-card title="Welcome">
            <p class="text-sm text-ink">Welcome, {{ auth()->user()->name }}.</p>
        </x-card>
    </div>
</x-app-layout>
