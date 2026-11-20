@php
    $user = auth()->user();
    $items = \App\Support\Navigation::for($user);
    $initials = \App\Support\Navigation::initials($user);
@endphp

<header x-data="{ drawer: false }" class="bg-deep text-white">
    <div class="mx-auto flex h-16 max-w-7xl items-center gap-4 px-4 sm:px-6 lg:px-8">
        <!-- Menu button (mobile) -->
        <button @click="drawer = true" type="button" class="-ms-2 rounded-md p-2 text-white/80 hover:bg-white/10 hover:text-white lg:hidden" aria-label="Open menu">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
        </button>

        <!-- Logo -->
        <a href="{{ route('dashboard') }}" class="flex shrink-0 items-center" aria-label="{{ config('app.name') }}">
            <img src="{{ asset('images/logo/logo-full-white.png') }}" alt="{{ config('app.name') }}" class="hidden h-12 w-auto sm:block">
            <img src="{{ asset('images/logo/icon-white.png') }}" alt="{{ config('app.name') }}" class="h-8 w-auto sm:hidden">
        </a>

        <!-- Menu (desktop) -->
        <nav class="ms-4 hidden h-full items-stretch gap-1 lg:flex" aria-label="Main">
            @foreach ($items as $item)
                <x-nav-link :href="route($item['route'])" :active="request()->routeIs($item['active'])">
                    {{ $item['label'] }}
                </x-nav-link>
            @endforeach
        </nav>

        <div class="ms-auto flex items-center gap-2">
            <!-- Account menu -->
            <x-dropdown align="right" width="w-64">
                <x-slot name="trigger">
                    <button type="button" class="flex items-center gap-2 rounded-full p-0.5 hover:bg-white/10" aria-label="Account menu">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-white/15 text-xs font-semibold text-white">{{ $initials }}</span>
                    </button>
                </x-slot>

                <x-slot name="content">
                    <div class="border-b border-line px-4 py-3">
                        <p class="truncate text-sm font-semibold text-ink">{{ $user->name }}</p>
                        <p class="truncate text-xs text-muted">{{ $user->email }}</p>
                        <p class="mt-1 text-xs text-muted">Role: {{ $user->role->label() }}</p>
                    </div>

                    <x-dropdown-link :href="route('profile.edit')">Profile</x-dropdown-link>

                    <form method="POST" action="{{ route('logout') }}" class="border-t border-line">
                        @csrf
                        <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                            Sign out
                        </x-dropdown-link>
                    </form>
                </x-slot>
            </x-dropdown>
        </div>
    </div>

    <!-- Slide-over menu (mobile) -->
    <div x-show="drawer" x-cloak class="fixed inset-0 z-50 lg:hidden" @keydown.escape.window="drawer = false">
        <div x-show="drawer" x-transition.opacity class="absolute inset-0 bg-ink/50" @click="drawer = false"></div>

        <div x-show="drawer"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="-translate-x-full"
             class="absolute inset-y-0 start-0 flex w-72 max-w-[85%] flex-col bg-white text-ink shadow-pop">
            <div class="flex h-14 items-center justify-between border-b border-line px-4">
                <img src="{{ asset('images/logo/logo-full-color.png') }}" alt="{{ config('app.name') }}" class="h-8 w-auto">
                <button @click="drawer = false" type="button" class="rounded-md p-2 text-muted hover:bg-canvas" aria-label="Close menu">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <nav class="flex-1 overflow-y-auto py-2" aria-label="Mobile">
                @foreach ($items as $item)
                    <x-responsive-nav-link :href="route($item['route'])" :active="request()->routeIs($item['active'])">
                        {{ $item['label'] }}
                    </x-responsive-nav-link>
                @endforeach
            </nav>

            <div class="border-t border-line p-4">
                <p class="truncate text-sm font-semibold">{{ $user->name }}</p>
                <p class="truncate text-xs text-muted">{{ $user->email }}</p>
                <div class="mt-3 flex gap-2">
                    <a href="{{ route('profile.edit') }}" class="flex-1 rounded-md border border-line px-3 py-2 text-center text-sm font-medium text-ink hover:bg-canvas">Profile</a>
                    <form method="POST" action="{{ route('logout') }}" class="flex-1">
                        @csrf
                        <button type="submit" class="w-full rounded-md border border-line px-3 py-2 text-sm font-medium text-ink hover:bg-canvas">Sign out</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
