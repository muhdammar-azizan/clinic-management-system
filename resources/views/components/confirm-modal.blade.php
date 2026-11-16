@props([
    'title' => 'Are you sure you want to delete this record?',
    'message' => 'This action cannot be undone.',
    'confirmLabel' => 'Yes, delete',
    'method' => 'DELETE',
])

{{--
    One confirmation dialog per page. Open it from any button with the form action:
    <button @click="$dispatch('confirm-delete', { action: '{{ route('...', $item) }}' })">Delete</button>
--}}
<div x-data="{ show: false, action: '' }"
     x-on:confirm-delete.window="action = $event.detail.action; show = true"
     x-on:keydown.escape.window="show = false"
     x-show="show"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center px-4"
     role="dialog" aria-modal="true">
    <div x-show="show" x-transition.opacity class="absolute inset-0 bg-ink/50" @click="show = false"></div>

    <div x-show="show"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="translate-y-2 opacity-0"
         x-transition:enter-end="translate-y-0 opacity-100"
         class="relative w-full max-w-md rounded-card bg-white p-6 text-center shadow-pop">
        <img src="{{ asset('images/logo/icon-color.png') }}" alt="" class="mx-auto h-12 w-auto">
        <h2 class="mt-4 text-base font-semibold text-deep">{{ $title }}</h2>
        <p class="mt-1 text-sm text-muted">{{ $message }}</p>

        <form method="POST" :action="action" class="mt-6 flex justify-center gap-3">
            @csrf
            @method($method)
            <x-button variant="secondary" @click="show = false">Cancel</x-button>
            <x-button variant="danger" type="submit">{{ $confirmLabel }}</x-button>
        </form>
    </div>
</div>
