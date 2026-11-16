<x-app-layout>
    <div class="mx-auto max-w-3xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <x-page-header title="Add Doctor" subtitle="The doctor signs in with the email and password you set here." />

        @include('admin.doctors._form')
    </div>
</x-app-layout>
