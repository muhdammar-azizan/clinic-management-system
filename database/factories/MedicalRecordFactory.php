<?php

namespace Database\Factories;

use App\Enums\ConditionStatus;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MedicalRecord>
 */
class MedicalRecordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id' => User::factory()->state(['role' => Role::Patient]),
            'doctor_id' => User::factory()->state(['role' => Role::Doctor]),
            'appointment_id' => null,
            'visit_date' => fake()->dateTimeBetween('-30 days', 'today')->format('Y-m-d'),
            'chief_complaint' => fake()->sentence(4),
            'symptoms' => fake()->sentence(),
            'diagnosis' => fake()->sentence(3),
            'treatment_plan' => fake()->sentence(),
            'prescription' => 'Paracetamol 500mg, twice daily after meals.',
            'condition' => ConditionStatus::Stable,
            'follow_up_date' => null,
        ];
    }
}
