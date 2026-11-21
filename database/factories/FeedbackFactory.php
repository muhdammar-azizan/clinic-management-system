<?php

namespace Database\Factories;

use App\Enums\FeedbackRating;
use App\Models\Feedback;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Feedback>
 */
class FeedbackFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'rating' => FeedbackRating::Easy,
            'area' => fake()->randomElement(array_keys(Feedback::AREAS)),
            'comment' => fake()->sentence(),
        ];
    }

    public function rated(FeedbackRating $rating): static
    {
        return $this->state(['rating' => $rating]);
    }
}
