@props([
    'empty' => 'Nothing to show yet.', // message shown when there are no rows
])

{{--
    Usage:
    <x-table empty="No appointments found.">
        <x-slot name="head"><tr><th>Doctor</th><th>Date</th></tr></x-slot>
        @foreach ($rows as $row) <tr><td>...</td></tr> @endforeach
    </x-table>
--}}
@if (trim((string) $slot) === '')
    <x-empty-state :title="$empty" />
@else
    <div class="overflow-x-auto rounded-card border border-line bg-white shadow-card">
        <table {{ $attributes->merge(['class' => 'min-w-full text-sm
            [&_th]:whitespace-nowrap [&_th]:px-4 [&_th]:py-3 [&_th]:text-start [&_th]:text-xs [&_th]:font-semibold [&_th]:text-muted
            [&_td]:px-4 [&_td]:py-3 [&_td]:align-middle
            [&_tbody_tr]:border-t [&_tbody_tr]:border-line [&_tbody_tr:hover]:bg-canvas']) }}>
            @isset($head)
                <thead class="bg-canvas">{{ $head }}</thead>
            @endisset
            <tbody>{{ $slot }}</tbody>
        </table>
    </div>
@endif
