<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\DoctorProfile;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_doctor_profile_links_user_and_service(): void
    {
        $profile = DoctorProfile::factory()->create();

        $this->assertSame(Role::Doctor, $profile->user->role);
        $this->assertInstanceOf(Service::class, $profile->service);
        $this->assertTrue($profile->user->doctorProfile->is($profile));
    }

    public function test_a_user_can_have_only_one_doctor_profile(): void
    {
        $profile = DoctorProfile::factory()->create();

        $this->expectException(QueryException::class);
        DoctorProfile::factory()->create(['user_id' => $profile->user_id]);
    }

    public function test_deleting_a_doctor_removes_the_profile(): void
    {
        $profile = DoctorProfile::factory()->create();
        $profile->user->delete();

        $this->assertDatabaseMissing('doctor_profiles', ['id' => $profile->id]);
    }

    public function test_user_profile_fields_are_saved(): void
    {
        $user = User::factory()->create(['phone' => '0123456789', 'date_of_birth' => '1999-05-20']);

        $this->assertSame('1999-05-20', $user->fresh()->date_of_birth->toDateString());
    }

    public function test_seeder_creates_demo_accounts_and_four_services(): void
    {
        $this->seed();

        $this->assertSame(4, Service::count());
        $this->assertNotNull(User::where('email', 'doctor@clinic.test')->first()->doctorProfile);
    }
}
