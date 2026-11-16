@props([
    'variant' => 'primary', // primary | secondary | accent | danger | ghost
    'size' => 'md',         // sm | md
    'href' => null,         // when set, renders a link styled as a button
    'type' => 'button',
])

{{--
    Accent (orange) is for one main call to action only, e.g. "Book Appointment".
    Use danger (red) for delete and cancel actions.
--}}
@php
$variants = [
    'primary' => 'bg-primary text-white hover:bg-primary-hover',
    'secondary' => 'border border-line bg-white text-ink hover:bg-canvas',
    'accent' => 'bg-accent text-white hover:bg-accent-hover',
    'danger' => 'bg-danger text-white hover:bg-danger-dark',
    'ghost' => 'text-primary hover:bg-primary-soft',
];

$sizes = [
    'sm' => 'px-3 py-1.5 text-xs',
    'md' => 'px-4 py-2 text-sm',
];

$classes = 'inline-flex items-center justify-center gap-2 rounded-md font-semibold transition disabled:pointer-events-none disabled:opacity-50 '
    .($variants[$variant] ?? $variants['primary']).' '
    .($sizes[$size] ?? $sizes['md']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
