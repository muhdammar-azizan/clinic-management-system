<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Route;

class Navigation
{
    /**
     * Header menu items for the user's role. Items without a registered route are skipped.
     *
     * @return list<array{label: string, route: string, active: string}>
     */
    public static function for(User $user): array
    {
        return collect(config('navigation.'.$user->role->value, []))
            ->filter(fn (array $item) => Route::has($item['route']))
            ->values()
            ->all();
    }

    /**
     * Up to two initials of the user's name, for the avatar.
     */
    public static function initials(User $user): string
    {
        return collect(explode(' ', trim($user->name)))
            ->filter()
            ->take(2)
            ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }
}
