@props(['disabled' => false])

<textarea @disabled($disabled) {{ $attributes->merge(['rows' => 4, 'class' => 'block w-full rounded-md border-line bg-white text-sm text-ink placeholder:text-muted/70 shadow-sm focus:border-primary focus:ring-1 focus:ring-primary disabled:bg-canvas disabled:text-muted']) }}>{{ $slot }}</textarea>
