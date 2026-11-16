@props([
    'status' => null,  // booked, done, cancelled, available, occupied, stable, recovering, critical
    'variant' => null, // success | warning | danger | info | neutral (overrides the status)
    'label' => null,
])

{{--
    Status colors: green = good, blue = booked or taken, yellow = needs care,
    red = critical, grey = inactive. Never use orange for a status.
--}}
@php
$variant ??= match (strtolower((string) $status)) {
    'done', 'completed', 'available', 'stable', 'active' => 'success',
    'booked', 'occupied' => 'info',
    'recovering', 'pending' => 'warning',
    'critical', 'failed' => 'danger',
    default => 'neutral',
};

$styles = [
    'success' => 'bg-success-soft text-success-dark',
    'info' => 'bg-primary-soft text-deep',
    'warning' => 'bg-warning-soft text-warning-dark',
    'danger' => 'bg-danger-soft text-danger-dark',
    'neutral' => 'bg-neutral-soft text-neutral-dark',
];

$dots = [
    'success' => 'bg-success',
    'info' => 'bg-primary',
    'warning' => 'bg-warning',
    'danger' => 'bg-danger',
    'neutral' => 'bg-neutral',
];

$text = $label ?? (trim((string) $slot) !== '' ? $slot : \Illuminate\Support\Str::headline((string) $status));
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold '.($styles[$variant] ?? $styles['neutral'])]) }}>
    <span class="h-1.5 w-1.5 rounded-full {{ $dots[$variant] ?? $dots['neutral'] }}"></span>
    {{ $text }}
</span>
