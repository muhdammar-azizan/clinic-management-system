<x-app-layout>
    <div class="mx-auto max-w-3xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <x-page-header title="Profile" subtitle="Manage your account details and password." />

        <x-card>@include('profile.partials.update-profile-information-form')</x-card>
        <x-card>@include('profile.partials.update-password-form')</x-card>
        <x-card>@include('profile.partials.delete-user-form')</x-card>
    </div>
</x-app-layout>
