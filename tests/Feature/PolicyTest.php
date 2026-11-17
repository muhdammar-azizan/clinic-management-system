<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\MedicalRecordAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class PolicyTest extends TestCase
{
    use RefreshDatabase;

    private function user(Role $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    public function test_appointment_policy(): void
    {
        $appointment = Appointment::factory()->create();
        $owner = $appointment->patient;
        $doctor = $appointment->slot->doctor;
        $strangers = [$this->user(Role::Patient), $this->user(Role::Doctor), $this->user(Role::Admin)];

        $this->assertTrue(Gate::forUser($owner)->allows('cancel', $appointment));
        $this->assertTrue(Gate::forUser($doctor)->allows('manage', $appointment));
        $this->assertTrue(Gate::forUser($doctor)->allows('writeRecord', $appointment));

        // The doctor cannot cancel as a patient and the patient cannot manage as a doctor.
        $this->assertFalse(Gate::forUser($doctor)->allows('cancel', $appointment));
        $this->assertFalse(Gate::forUser($owner)->allows('manage', $appointment));
        $this->assertFalse(Gate::forUser($owner)->allows('writeRecord', $appointment));

        foreach ($strangers as $stranger) {
            foreach (['cancel', 'manage', 'writeRecord'] as $ability) {
                $this->assertFalse(Gate::forUser($stranger)->allows($ability, $appointment), "{$stranger->role->value} should not be allowed to {$ability}");
            }
        }
    }

    public function test_medical_record_policy(): void
    {
        $record = MedicalRecord::factory()->create();

        $this->assertTrue(Gate::forUser($record->doctor)->allows('view', $record));
        $this->assertTrue(Gate::forUser($record->doctor)->allows('manage', $record));
        $this->assertTrue(Gate::forUser($record->patient)->allows('view', $record));
        $this->assertFalse(Gate::forUser($record->patient)->allows('manage', $record), 'Patients are read-only.');

        foreach ([Role::Admin, Role::Doctor, Role::Patient] as $role) {
            $stranger = $this->user($role);
            $this->assertFalse(Gate::forUser($stranger)->allows('view', $record), "{$role->value} stranger can view");
            $this->assertFalse(Gate::forUser($stranger)->allows('manage', $record), "{$role->value} stranger can manage");
        }
    }

    public function test_attachment_policy_gives_patients_prescriptions_only(): void
    {
        $record = MedicalRecord::factory()->create();
        $prescription = MedicalRecordAttachment::factory()->prescription()->create(['medical_record_id' => $record->id]);
        $lab = MedicalRecordAttachment::factory()->create(['medical_record_id' => $record->id]);

        $this->assertTrue(Gate::forUser($record->doctor)->allows('download', $prescription));
        $this->assertTrue(Gate::forUser($record->doctor)->allows('download', $lab));
        $this->assertTrue(Gate::forUser($record->patient)->allows('download', $prescription));
        $this->assertFalse(Gate::forUser($record->patient)->allows('download', $lab));

        foreach ([Role::Admin, Role::Doctor, Role::Patient] as $role) {
            $this->assertFalse(Gate::forUser($this->user($role))->allows('download', $prescription), "{$role->value} stranger can download");
        }
    }

    public function test_files_of_a_deleted_record_are_not_downloadable(): void
    {
        $record = MedicalRecord::factory()->create();
        $prescription = MedicalRecordAttachment::factory()->prescription()->create(['medical_record_id' => $record->id]);
        $record->delete();

        $this->assertFalse(Gate::forUser($record->doctor)->allows('download', $prescription->fresh()));
        $this->assertFalse(Gate::forUser($record->patient)->allows('download', $prescription->fresh()));
    }
}
