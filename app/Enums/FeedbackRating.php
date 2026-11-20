<?php

namespace App\Enums;

/**
 * How easy the user found the system, scored as in the SQA report: Easy 3, Moderate 2, Hard 1.
 */
enum FeedbackRating: int
{
    case Hard = 1;
    case Moderate = 2;
    case Easy = 3;

    /** A score at or above this is acceptable usability (SQA report). */
    public const ACCEPTABLE_SCORE = 2.5;

    public function label(): string
    {
        return $this->name;
    }

    /** Badge color: green for easy, yellow for moderate, red for hard. */
    public function variant(): string
    {
        return match ($this) {
            self::Easy => 'success',
            self::Moderate => 'warning',
            self::Hard => 'danger',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Easy => 'I finished what I wanted without any trouble.',
            self::Moderate => 'It worked, but some steps were confusing.',
            self::Hard => 'I struggled or could not finish what I wanted.',
        };
    }
}
