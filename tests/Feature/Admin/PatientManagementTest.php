<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => Role::Admin]);
    }

    public function test_only_admin_can_open_the_patient_pages(): void
    {
        $patient = User::factory()->create();

        $this->get('/admin/patients')->assertRedirect('/login');

        foreach ([Role::Patient, Role::Doctor] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get("/admin/patients/{$patient->id}")->assertForbidden();
        }

        $this->actingAs($this->admin())->get('/admin/patients')->assertOk()->assertSee($patient->name);
    }

    public function test_index_lists_only_patients_and_supports_search(): void
    {
        $match = User::factory()->create(['name' => 'Aisyah Rahman']);
        $other = User::factory()->create(['name' => 'Daniel Lim']);
        $doctor = User::factory()->create(['role' => Role::Doctor, 'name' => 'Dr. Hidden']);

        $this->actingAs($this->admin())->get('/admin/patients')
            ->assertSee('Aisyah Rahman')->assertSee('Daniel Lim')->assertDontSee('Dr. Hidden');

        $this->actingAs($this->admin())->get('/admin/patients?search=aisyah')
            ->assertSee('Aisyah Rahman')->assertDontSee('Daniel Lim');
    }

    public function test_show_lists_appointments_but_never_the_diagnosis(): void
    {
        $appointment = Appointment::factory()->create();
        MedicalRecord::factory()->create([
            'patient_id' => $appointment->patient_id,
            'diagnosis' => 'Secret diagnosis text',
            'prescription' => 'Secret prescription text',
        ]);

        $this->actingAs($this->admin())->get("/admin/patients/{$appointment->patient_id}")
            ->assertOk()
            ->assertSee($appointment->appointment_code)
            ->assertSee($appointment->service->name)
            ->assertDontSee('Secret diagnosis text')
            ->assertDontSee('Secret prescription text');
    }

    public function test_admin_can_update_a_patient(): void
    {
        $patient = User::factory()->create();

        $this->actingAs($this->admin())->put("/admin/patients/{$patient->id}", [
            'name' => 'New Name',
            'email' => 'new@example.com',
            'phone' => '011-2223333',
            'gender' => 'male',
        ])->assertRedirect("/admin/patients/{$patient->id}")->assertSessionHas('status');

        $patient->refresh();
        $this->assertSame('New Name', $patient->name);
        $this->assertSame('011-2223333', $patient->phone);
        $this->assertNull($patient->email_verified_at);
    }

    public function test_update_validates_unique_email_and_cannot_change_role(): void
    {
        $patient = User::factory()->create();
        $taken = User::factory()->create();

        $this->actingAs($this->admin())->put("/admin/patients/{$patient->id}", [
            'name' => 'X', 'email' => $taken->email,
        ])->assertSessionHasErrors('email');

        $this->actingAs($this->admin())->put("/admin/patients/{$patient->id}", [
            'name' => 'X', 'email' => 'x@example.com', 'role' => 'admin',
        ]);
        $this->assertSame(Role::Patient, $patient->fresh()->role);
    }

    public function test_admin_can_delete_a_patient_with_their_data(): void
    {
        $appointment = Appointment::factory()->create();
        $record = MedicalRecord::factory()->create(['patient_id' => $appointment->patient_id]);

        $this->actingAs($this->admin())->delete("/admin/patients/{$appointment->patient_id}")
            ->assertRedirect('/admin/patients')->assertSessionHas('status');

        $this->assertDatabaseMissing('users', ['id' => $appointment->patient_id]);
        $this->assertDatabaseMissing('appointments', ['id' => $appointment->id]);
        $this->assertDatabaseMissing('medical_records', ['id' => $record->id]);
    }

    public function test_non_patient_users_return_404(): void
    {
        $doctor = User::factory()->create(['role' => Role::Doctor]);

        $this->actingAs($this->admin())->get("/admin/patients/{$doctor->id}")->assertNotFound();
        $this->actingAs($this->admin())->delete("/admin/patients/{$doctor->id}")->assertNotFound();
        $this->assertNotNull($doctor->fresh());
    }
}
