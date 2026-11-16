@props([
    'title' => null,
    'padding' => true, // set to false for tables or lists that should touch the card edges
])

<div {{ $attributes->merge(['class' => 'rounded-card border border-line bg-white shadow-card']) }}>
    @if ($title || isset($actions))
        <div class="flex items-center justify-between gap-3 border-b border-line px-5 py-3">
            <h3 class="text-sm font-semibold text-deep">{{ $title }}</h3>
            @isset($actions)
                <div class="flex items-center gap-2">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    <div @class(['p-5' => $padding])>
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="border-t border-line px-5 py-3">{{ $footer }}</div>
    @endisset
</div>
