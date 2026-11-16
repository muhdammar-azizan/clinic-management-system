<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Enums\Role;
use App\Enums\SlotStatus;
use App\Models\RosterSlot;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Appointment>
 */
class AppointmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id' => User::factory()->state(['role' => Role::Patient]),
            'roster_slot_id' => RosterSlot::factory()->state(['status' => SlotStatus::Occupied]),
            'service_id' => Service::factory(),
            'status' => AppointmentStatus::Booked,
            'reason' => fake()->sentence(),
        ];
    }

    public function completed(): static
    {
        return $this->state(['status' => AppointmentStatus::Completed]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => AppointmentStatus::Cancelled]);
    }
}
