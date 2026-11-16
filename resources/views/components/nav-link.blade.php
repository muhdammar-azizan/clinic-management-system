@props(['active'])

{{-- Header link on the Deep Blue bar. The active link gets an accent underline. --}}
@php
$classes = ($active ?? false)
    ? 'inline-flex items-center border-b-2 border-accent px-3 text-sm font-semibold text-white'
    : 'inline-flex items-center border-b-2 border-transparent px-3 text-sm font-medium text-white/75 transition hover:text-white';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
