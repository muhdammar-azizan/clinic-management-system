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
     * Browser tab title for the current page: the label of the active menu item, or null if none matches.
     */
    public static function pageTitle(?User $user): ?string
    {
        if (request()->routeIs('profile.*')) {
            return 'Profile';
        }

        if (! $user) {
            return null;
        }

        foreach (static::for($user) as $item) {
            if (request()->routeIs($item['active'])) {
                return $item['label'];
            }
        }

        return null;
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
