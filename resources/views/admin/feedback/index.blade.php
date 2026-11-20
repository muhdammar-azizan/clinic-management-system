<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <x-page-header title="Feedback" subtitle="How users rate the system. Scores: Easy 3, Moderate 2, Hard 1." />

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
            <x-stat-card label="Score" :value="$average !== null ? number_format($average, 2).' / 3' : '-'"
                         :hint="$average === null ? 'No feedback yet' : ($acceptable ? 'Acceptable (2.5 or more)' : 'Below the 2.5 target')" />
            <x-stat-card label="Responses" :value="$total" />
            @foreach (\App\Enums\FeedbackRating::cases() as $rating)
                <x-stat-card :label="$rating->label()" :value="$counts[$rating->value]"
                             :hint="$total > 0 ? round($counts[$rating->value] / $total * 100).'% of responses' : null" />
            @endforeach
        </div>

        <x-card>
            <form method="GET" class="space-y-4">
                <div class="grid gap-3 sm:grid-cols-3">
                    <x-field label="Search" name="search">
                        <x-text-input name="search" type="search" class="block w-full" placeholder="Comment or user" :value="request('search')" />
                    </x-field>

                    <x-field label="Rating" name="rating">
                        <x-select name="rating" class="block w-full">
                            <option value="">All ratings</option>
                            @foreach (\App\Enums\FeedbackRating::cases() as $rating)
                                <option value="{{ $rating->value }}" @selected((int) request('rating') === $rating->value)>{{ $rating->label() }}</option>
                            @endforeach
                        </x-select>
                    </x-field>

                    <x-field label="Area" name="area">
                        <x-select name="area" class="block w-full">
                            <option value="">All areas</option>
                            @foreach ($areas as $key => $label)
                                <option value="{{ $key }}" @selected(request('area') === $key)>{{ $label }}</option>
                            @endforeach
                        </x-select>
                    </x-field>
                </div>

                <div class="flex gap-2">
                    <x-button type="submit">Filter</x-button>
                    @if ($filtered)
                        <x-button :href="route('admin.feedback.index')" variant="ghost">Clear</x-button>
                    @endif
                </div>
            </form>
        </x-card>

        <div class="grid gap-6 lg:grid-cols-3">
            <x-card title="Score by area" :padding="false" class="lg:col-span-1">
                <x-table empty="No feedback yet.">
                    @if ($byArea->isNotEmpty())
                        <x-slot name="head">
                            <tr><th>Area</th><th>Responses</th><th>Score</th></tr>
                        </x-slot>
                    @endif

                    @foreach ($areas as $key => $label)
                        @continue(! $byArea->has($key))
                        <tr>
                            <td>{{ $label }}</td>
                            <td>{{ $byArea[$key]['count'] }}</td>
                            <td class="font-medium {{ $byArea[$key]['average'] >= \App\Enums\FeedbackRating::ACCEPTABLE_SCORE ? 'text-success-dark' : 'text-danger-dark' }}">{{ number_format($byArea[$key]['average'], 2) }}</td>
                        </tr>
                    @endforeach
                </x-table>
            </x-card>

            <x-card title="Responses" :padding="false" class="lg:col-span-2">
                <x-table :empty="$filtered ? 'No feedback matches your filters.' : 'No feedback has been sent yet.'">
                    @if ($feedback->isNotEmpty())
                        <x-slot name="head">
                            <tr><th>Date</th><th>User</th><th>Area</th><th>Rating</th><th>Comment</th></tr>
                        </x-slot>
                    @endif

                    @foreach ($feedback as $item)
                        <tr>
                            <td class="whitespace-nowrap">{{ $item->created_at->format('d M Y') }}</td>
                            <td>
                                <div class="font-medium text-ink">{{ $item->user->name }}</div>
                                <div class="text-xs text-muted">{{ $item->user->role->label() }}</div>
                            </td>
                            <td>{{ $item->areaLabel() }}</td>
                            <td><x-badge :variant="$item->rating->variant()" :label="$item->rating->label()" /></td>
                            <td class="max-w-xs">{{ $item->comment ?? '-' }}</td>
                        </tr>
                    @endforeach
                </x-table>
            </x-card>
        </div>

        {{ $feedback->links() }}
    </div>
</x-app-layout>
