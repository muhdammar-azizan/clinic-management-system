@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-card bg-success-soft px-3 py-2 text-sm font-medium text-success-dark']) }}>
        {{ $status }}
    </div>
@endif
