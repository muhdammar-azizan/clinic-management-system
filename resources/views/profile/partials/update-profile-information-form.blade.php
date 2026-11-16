<section>
    <header>
        <h2 class="text-base font-semibold text-deep">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-muted">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-ink">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-primary hover:text-primary-hover rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-success-dark">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <x-field label="Phone" name="phone">
                <x-text-input id="phone" name="phone" type="tel" class="block w-full" :value="old('phone', $user->phone)" autocomplete="tel" />
            </x-field>

            <x-field label="Date of birth" name="date_of_birth">
                <x-text-input id="date_of_birth" name="date_of_birth" type="date" class="block w-full" :value="old('date_of_birth', $user->date_of_birth?->toDateString())" max="{{ now()->subDay()->toDateString() }}" />
            </x-field>

            <x-field label="Gender" name="gender">
                <x-select id="gender" name="gender" class="block w-full">
                    <option value="">Prefer not to say</option>
                    <option value="male" @selected(old('gender', $user->gender) === 'male')>Male</option>
                    <option value="female" @selected(old('gender', $user->gender) === 'female')>Female</option>
                </x-select>
            </x-field>

            <x-field label="Address" name="address">
                <x-text-input id="address" name="address" type="text" class="block w-full" :value="old('address', $user->address)" autocomplete="street-address" />
            </x-field>
        </div>

        @if ($user->isDoctor() && $user->doctorProfile)
            <dl class="grid gap-4 rounded-card bg-canvas p-4 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-muted">Service</dt>
                    <dd class="font-medium text-ink">{{ $user->doctorProfile->service->name }}</dd>
                </div>
                <div>
                    <dt class="text-muted">License number</dt>
                    <dd class="font-medium text-ink">{{ $user->doctorProfile->license_number ?? '-' }}</dd>
                </div>
            </dl>
        @endif

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-muted"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
