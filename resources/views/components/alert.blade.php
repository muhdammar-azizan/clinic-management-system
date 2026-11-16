@props([
    'variant' => 'success', // success | info | warning | danger
])

@php
$styles = [
    'success' => 'border-success/30 bg-success-soft text-success-dark',
    'info' => 'border-primary/30 bg-primary-soft text-deep',
    'warning' => 'border-warning/40 bg-warning-soft text-warning-dark',
    'danger' => 'border-danger/30 bg-danger-soft text-danger-dark',
];
@endphp

<div role="alert" {{ $attributes->merge(['class' => 'rounded-md border px-4 py-3 text-sm font-medium '.($styles[$variant] ?? $styles['info'])]) }}>
    {{ $slot }}
</div>
