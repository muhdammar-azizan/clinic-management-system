<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\Appointment;
use App\Models\DoctorProfile;
use App\Models\MedicalRecord;
use App\Models\RosterSlot;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => Role::Admin]);
    }

    private function payload(Service $service, array $overrides = []): array
    {
        return array_merge([
            'name' => 'Dr. Aisyah',
            'email' => 'aisyah@clinic.test',
            'password' => 'Secret-pass-123',
            'password_confirmation' => 'Secret-pass-123',
            'service_id' => $service->id,
            'license_number' => 'MMC-777',
            'phone' => '012-3456789',
            'bio' => 'Family doctor.',
        ], $overrides);
    }

    public function test_only_admin_can_open_the_doctor_pages(): void
    {
        $this->get('/admin/doctors')->assertRedirect('/login');

        foreach ([Role::Patient, Role::Doctor] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))->get('/admin/doctors')->assertForbidden();
        }

        $this->actingAs($this->admin())->get('/admin/doctors')->assertOk();
    }

    public function test_admin_can_create_a_doctor_who_can_log_in(): void
    {
        $service = Service::factory()->create();

        $this->actingAs($this->admin())
            ->post('/admin/doctors', $this->payload($service))
            ->assertRedirect('/admin/doctors')
            ->assertSessionHas('status');

        $doctor = User::where('email', 'aisyah@clinic.test')->first();
        $this->assertSame(Role::Doctor, $doctor->role);
        $this->assertSame($service->id, $doctor->doctorProfile->service_id);

        auth()->logout();
        $this->post('/login', ['email' => 'aisyah@clinic.test', 'password' => 'Secret-pass-123']);
        $this->assertAuthenticatedAs($doctor);
    }

    public function test_create_requires_password_and_unique_email(): void
    {
        $service = Service::factory()->create();
        $existing = User::factory()->create();

        $this->actingAs($this->admin())
            ->post('/admin/doctors', $this->payload($service, ['password' => '', 'password_confirmation' => '', 'email' => $existing->email]))
            ->assertSessionHasErrors(['password', 'email']);
    }

    public function test_admin_can_update_a_doctor_and_keep_the_password_when_blank(): void
    {
        $profile = DoctorProfile::factory()->create();
        $doctor = $profile->user;
        $oldHash = $doctor->password;
        $newService = Service::factory()->create();

        $this->actingAs($this->admin())
            ->put("/admin/doctors/{$doctor->id}", $this->payload($newService, ['email' => $doctor->email, 'password' => '', 'password_confirmation' => '']))
            ->assertSessionHasNoErrors();

        $doctor->refresh();
        $this->assertSame('Dr. Aisyah', $doctor->name);
        $this->assertSame($oldHash, $doctor->password);
        $this->assertSame($newService->id, $doctor->doctorProfile->service_id);
    }

    public function test_pages_for_non_doctor_users_return_404(): void
    {
        $patient = User::factory()->create();

        $this->actingAs($this->admin())->get("/admin/doctors/{$patient->id}/edit")->assertNotFound();
        $this->actingAs($this->admin())->delete("/admin/doctors/{$patient->id}")->assertNotFound();
        $this->assertNotNull($patient->fresh());
    }

    public function test_index_can_search_and_filter_by_service(): void
    {
        $gp = DoctorProfile::factory()->create();
        $other = DoctorProfile::factory()->create();

        $this->actingAs($this->admin())->get('/admin/doctors?service='.$gp->service_id)
            ->assertSee($gp->user->name)
            ->assertDontSee($other->user->name);

        $this->actingAs($this->admin())->get('/admin/doctors?search='.urlencode($other->user->email))
            ->assertSee($other->user->name)
            ->assertDontSee($gp->user->name);
    }

    public function test_admin_can_delete_a_doctor_without_history(): void
    {
        $profile = DoctorProfile::factory()->create();

        $this->actingAs($this->admin())->delete("/admin/doctors/{$profile->user_id}")
            ->assertRedirect('/admin/doctors')
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('users', ['id' => $profile->user_id]);
        $this->assertDatabaseMissing('doctor_profiles', ['id' => $profile->id]);
    }

    public function test_doctor_with_medical_records_cannot_be_deleted(): void
    {
        $record = MedicalRecord::factory()->create();

        $this->actingAs($this->admin())->delete("/admin/doctors/{$record->doctor_id}")
            ->assertSessionHas('error');

        $this->assertNotNull($record->doctor->fresh());
    }

    public function test_doctor_with_booked_appointments_cannot_be_deleted(): void
    {
        $slot = RosterSlot::factory()->occupied()->create();
        Appointment::factory()->create(['roster_slot_id' => $slot->id]);

        $this->actingAs($this->admin())->delete("/admin/doctors/{$slot->doctor_id}")
            ->assertSessionHas('error');

        $this->assertNotNull($slot->doctor->fresh());
    }
}
