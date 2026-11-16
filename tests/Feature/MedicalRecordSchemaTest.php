<?php

namespace Tests\Feature;

use App\Enums\ConditionStatus;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\MedicalRecordAttachment;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedicalRecordSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_record_links_patient_doctor_and_attachments(): void
    {
        $record = MedicalRecord::factory()->create();
        MedicalRecordAttachment::factory()->prescription()->create(['medical_record_id' => $record->id]);

        $this->assertTrue($record->patient->isPatient());
        $this->assertTrue($record->doctor->isDoctor());
        $this->assertSame(ConditionStatus::Stable, $record->fresh()->condition);
        $this->assertTrue($record->attachments->first()->isPrescription());
        $this->assertSame('MR-'.str_pad((string) $record->id, 5, '0', STR_PAD_LEFT), $record->record_code);
    }

    public function test_an_appointment_has_at_most_one_record(): void
    {
        $appointment = Appointment::factory()->create();
        MedicalRecord::factory()->create(['appointment_id' => $appointment->id]);

        $this->expectException(QueryException::class);
        MedicalRecord::factory()->create(['appointment_id' => $appointment->id]);
    }

    public function test_deleting_a_record_is_a_soft_delete(): void
    {
        $record = MedicalRecord::factory()->create();
        $record->delete();

        $this->assertSoftDeleted($record);
        $this->assertSame(0, MedicalRecord::count());
        $this->assertSame(1, MedicalRecord::withTrashed()->count());
    }

    public function test_deleting_a_patient_removes_records_and_attachments(): void
    {
        $record = MedicalRecord::factory()->create();
        MedicalRecordAttachment::factory()->create(['medical_record_id' => $record->id]);

        $record->patient->delete();

        $this->assertSame(0, MedicalRecord::withTrashed()->count());
        $this->assertSame(0, MedicalRecordAttachment::count());
    }

    public function test_a_doctor_with_records_cannot_be_deleted(): void
    {
        $record = MedicalRecord::factory()->create();

        $this->expectException(QueryException::class);
        $record->doctor->delete();
    }
}
