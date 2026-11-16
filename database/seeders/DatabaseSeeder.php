<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed demo accounts. All passwords are "password".
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@clinic.test',
            'role' => Role::Admin,
        ]);

        User::factory()->create([
            'name' => 'Dr. Demo',
            'email' => 'doctor@clinic.test',
            'role' => Role::Doctor,
        ]);

        User::factory()->create([
            'name' => 'Patient Demo',
            'email' => 'patient@clinic.test',
            'role' => Role::Patient,
        ]);
    }
}
