<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;

class Week
{
    /**
     * Monday of the week that contains the given Y-m-d date. Falls back to the current week.
     */
    public static function start(?string $date): Carbon
    {
        try {
            $anchor = $date ? Carbon::createFromFormat('Y-m-d', $date)->startOfDay() : today();
        } catch (InvalidFormatException) {
            $anchor = today();
        }

        return $anchor->startOfWeek(Carbon::MONDAY);
    }
}
