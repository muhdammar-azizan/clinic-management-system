<x-app-layout>
    <div class="mx-auto max-w-2xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <x-page-header title="Give Feedback" subtitle="Tell us how easy the system was to use. It takes less than a minute." />

        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif

        <form method="POST" action="{{ route('feedback.store') }}" class="space-y-6">
            @csrf

            <x-card title="Your experience">
                <fieldset>
                    <legend class="text-sm font-medium text-ink">How easy was it? <span class="text-danger">*</span></legend>

                    <div class="mt-2 grid gap-2 sm:grid-cols-3">
                        @foreach ($ratings as $rating)
                            <label class="cursor-pointer">
                                <input type="radio" name="rating" value="{{ $rating->value }}" class="peer sr-only" @checked((int) old('rating') === $rating->value) required>
                                <span class="block h-full rounded-card border border-line bg-white p-3 text-sm transition hover:border-primary peer-checked:border-primary peer-checked:bg-primary-soft peer-focus-visible:ring-2 peer-focus-visible:ring-primary">
                                    <span class="block font-semibold text-deep">{{ $rating->label() }}</span>
                                    <span class="mt-1 block text-muted">{{ $rating->description() }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('rating')" class="mt-2" />
                </fieldset>

                <div class="mt-5 space-y-5">
                    <x-field label="What did you use?" name="area" required>
                        <x-select id="area" name="area" class="block w-full" required>
                            <option value="">Select one</option>
                            @foreach ($areas as $key => $label)
                                <option value="{{ $key }}" @selected(old('area', $selectedArea) === $key)>{{ $label }}</option>
                            @endforeach
                        </x-select>
                    </x-field>

                    <x-field label="Comment" name="comment" hint="Optional. What was easy, and what could be better?">
                        <x-textarea id="comment" name="comment" rows="4" class="block w-full" maxlength="1000">{{ old('comment') }}</x-textarea>
                    </x-field>
                </div>

                <x-slot name="footer">
                    <div class="flex justify-end">
                        <x-button type="submit">Send feedback</x-button>
                    </div>
                </x-slot>
            </x-card>
        </form>
    </div>
</x-app-layout>
