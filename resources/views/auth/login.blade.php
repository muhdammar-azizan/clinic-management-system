<x-guest-layout>
    <h1 class="text-xl font-semibold text-deep">Sign in</h1>
    <p class="mt-1 text-sm text-muted">Welcome back. Enter your details to continue.</p>

    <x-auth-session-status class="mt-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
        @csrf

        <x-field label="Email" name="email">
            <x-text-input id="email" class="block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
        </x-field>

        <x-field label="Password" name="password">
            <x-text-input id="password" class="block w-full" type="password" name="password" required autocomplete="current-password" />
        </x-field>

        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-line text-primary shadow-sm focus:ring-primary" name="remember">
                <span class="ms-2 text-sm text-muted">Remember me</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm" href="{{ route('password.request') }}">Forgot password?</a>
            @endif
        </div>

        <x-primary-button class="w-full justify-center">Sign in</x-primary-button>

        <p class="text-center text-sm text-muted">
            New patient? <a href="{{ route('register') }}" class="font-medium">Create an account</a>
        </p>
    </form>
</x-guest-layout>
