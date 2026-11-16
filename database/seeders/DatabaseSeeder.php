<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\DoctorProfile;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed demo accounts and services. All passwords are "password".
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@clinic.test',
            'role' => Role::Admin,
        ]);

        $services = collect([
            'General Practitioner' => 'Everyday health concerns and general check-ups.',
            'Pediatrician' => 'Healthcare for babies, children and teenagers.',
            'Family Medicine' => 'Ongoing care for the whole family.',
            'Gynecologist' => 'Reproductive health care for women.',
        ])->map(fn ($description, $name) => Service::create(['name' => $name, 'description' => $description]));

        $doctor = User::factory()->create([
            'name' => 'Dr. Demo',
            'email' => 'doctor@clinic.test',
            'role' => Role::Doctor,
        ]);

        DoctorProfile::create([
            'user_id' => $doctor->id,
            'service_id' => $services['General Practitioner']->id,
            'license_number' => 'MMC-00001',
            'bio' => 'Demo general practitioner.',
        ]);

        User::factory()->create([
            'name' => 'Patient Demo',
            'email' => 'patient@clinic.test',
            'role' => Role::Patient,
        ]);
    }
}
