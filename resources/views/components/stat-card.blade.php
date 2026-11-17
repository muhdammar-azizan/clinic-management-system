@props([
    'label',
    'value',
    'hint' => null,
    'href' => null, // makes the whole card a link
])

@php
    $classes = 'block rounded-card border border-line bg-white p-4 shadow-card';
    if ($href) {
        $classes .= ' transition hover:border-primary';
    }
@endphp

<{{ $href ? 'a' : 'div' }} @if ($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => $classes]) }}>
    <p class="text-xs font-medium uppercase tracking-wide text-muted">{{ $label }}</p>
    <p class="mt-1 text-2xl font-semibold text-deep">{{ $value }}</p>
    @if ($hint)
        <p class="mt-0.5 text-xs text-muted">{{ $hint }}</p>
    @endif
</{{ $href ? 'a' : 'div' }}>
