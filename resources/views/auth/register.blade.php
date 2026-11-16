<x-guest-layout>
    <h1 class="text-xl font-semibold text-deep">Create a patient account</h1>
    <p class="mt-1 text-sm text-muted">Register to book appointments and view your records.</p>

    <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
        @csrf

        <x-field label="Full name" name="name" required>
            <x-text-input id="name" class="block w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
        </x-field>

        <x-field label="Email" name="email" required>
            <x-text-input id="email" class="block w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
        </x-field>

        <x-field label="Password" name="password" required>
            <x-text-input id="password" class="block w-full" type="password" name="password" required autocomplete="new-password" />
        </x-field>

        <x-field label="Confirm password" name="password_confirmation" required>
            <x-text-input id="password_confirmation" class="block w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
        </x-field>

        <x-primary-button class="w-full justify-center">Create account</x-primary-button>

        <p class="text-center text-sm text-muted">
            Already registered? <a href="{{ route('login') }}" class="font-medium">Sign in</a>
        </p>
    </form>
</x-guest-layout>
