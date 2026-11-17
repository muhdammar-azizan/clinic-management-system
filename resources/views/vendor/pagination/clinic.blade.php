@if ($paginator->hasPages())
    @php
        $link = 'inline-flex h-8 min-w-8 items-center justify-center rounded-md border px-2 text-sm font-medium';
    @endphp

    <nav role="navigation" aria-label="Pagination" class="flex flex-wrap items-center justify-between gap-3 text-sm">
        <p class="text-muted">
            Showing <span class="font-medium text-ink">{{ $paginator->firstItem() }}</span>
            to <span class="font-medium text-ink">{{ $paginator->lastItem() }}</span>
            of <span class="font-medium text-ink">{{ $paginator->total() }}</span> results
        </p>

        <div class="flex items-center gap-1">
            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <span class="{{ $link }} cursor-not-allowed border-line bg-canvas text-muted" aria-disabled="true">Previous</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $link }} border-line bg-white text-ink hover:border-primary hover:text-primary">Previous</a>
            @endif

            {{-- Page numbers (only the current page shows on small screens) --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="{{ $link }} hidden border-transparent text-muted sm:inline-flex">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="{{ $link }} border-primary bg-primary text-white" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="{{ $link }} hidden border-line bg-white text-ink hover:border-primary hover:text-primary sm:inline-flex" aria-label="Go to page {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $link }} border-line bg-white text-ink hover:border-primary hover:text-primary">Next</a>
            @else
                <span class="{{ $link }} cursor-not-allowed border-line bg-canvas text-muted" aria-disabled="true">Next</span>
            @endif
        </div>
    </nav>
@endif
