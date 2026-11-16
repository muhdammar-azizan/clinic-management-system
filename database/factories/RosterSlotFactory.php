<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Enums\SlotStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RosterSlot>
 */
class RosterSlotFactory extends Factory
{
    public function definition(): array
    {
        return [
            'doctor_id' => User::factory()->state(['role' => Role::Doctor]),
            'slot_date' => fake()->dateTimeBetween('+1 day', '+14 days')->format('Y-m-d'),
            'start_time' => fake()->randomElement(['09:00', '09:30', '10:00', '10:30', '11:00', '14:00', '14:30', '15:00']),
            'status' => SlotStatus::Available,
            'notes' => null,
        ];
    }

    public function occupied(): static
    {
        return $this->state(['status' => SlotStatus::Occupied]);
    }
}
