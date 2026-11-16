@props(['active'])

{{-- Link inside the mobile slide-over menu. --}}
@php
$classes = ($active ?? false)
    ? 'block border-s-4 border-accent bg-primary-soft px-4 py-3 text-sm font-semibold text-deep'
    : 'block border-s-4 border-transparent px-4 py-3 text-sm font-medium text-ink hover:bg-canvas';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
