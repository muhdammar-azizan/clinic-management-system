<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\DoctorProfile;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DoctorProfile>
 */
class DoctorProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => Role::Doctor]),
            'service_id' => Service::factory(),
            'license_number' => 'MMC-'.fake()->unique()->numerify('#####'),
            'bio' => fake()->paragraph(),
        ];
    }
}
