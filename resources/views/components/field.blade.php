@props([
    'label' => null,
    'name' => null,     // input name, used for the label "for" and the validation error
    'hint' => null,
    'required' => false,
])

{{--
    Wraps a label, an input and its validation error.
    <x-field label="Email" name="email"><x-text-input id="email" name="email" type="email" /></x-field>
--}}
<div {{ $attributes->merge(['class' => 'space-y-1.5']) }}>
    @if ($label)
        <x-input-label :for="$name">
            {{ $label }}@if ($required)<span class="text-danger"> *</span>@endif
        </x-input-label>
    @endif

    {{ $slot }}

    @if ($hint)
        <p class="text-xs text-muted">{{ $hint }}</p>
    @endif

    @if ($name && isset($errors))
        <x-input-error :messages="$errors->get($name)" />
    @endif
</div>
