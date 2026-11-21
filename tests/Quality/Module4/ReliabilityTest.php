<?php

namespace Tests\Quality\Module4;

use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\MedicalRecordAttachment;
use App\Models\RosterSlot;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;
use Tests\Quality\QualityTestCase;

/**
 * Module 4 (SQA report), Reliability: foreign keys, confirmation before delete,
 * transactions, and zero accidental data loss.
 */
class ReliabilityTest extends QualityTestCase
{
    private User $doctor;

    private User $patient;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->doctor = $this->doctor();
        $this->patient = $this->patient();
    }

    #[Group('M4-REL-1')]
    public function test_a_record_for_a_patient_that_does_not_exist_is_refused_by_the_database(): void
    {
        // The report's check: patient_id=99999 must fail with a constraint error.
        $this->expectException(QueryException::class);

        MedicalRecord::factory()->create(['patient_id' => 99999, 'doctor_id' => $this->doctor->id]);
    }

    #[Group('M4-REL-1')]
    public function test_a_record_for_a_doctor_that_does_not_exist_is_refused_by_the_database(): void
    {
        $this->expectException(QueryException::class);

        MedicalRecord::factory()->create(['patient_id' => $this->patient->id, 'doctor_id' => 99999]);
    }

    #[Group('M4-REL-2')]
    public function test_every_delete_button_goes_through_the_confirmation_dialog(): void
    {
        $records = MedicalRecord::factory()->count(3)->create(['doctor_id' => $this->doctor->id, 'patient_id' => $this->patient->id]);

        $html = $this->actingAs($this->doctor)->get('/doctor/records')
            ->assertSee('Are you sure you want to delete this record?')
            ->getContent();

        // One shared dialog holds the only delete form, and it has no address until a button fills it in.
        $this->assertSame(1, substr_count($html, 'name="_method" value="DELETE"'));
        $this->assertStringContainsString(':action="action"', $html);
        $this->assertSame(3, substr_count($html, "\$dispatch('confirm-delete'"));

        foreach ($records as $record) {
            $this->assertStringNotContainsString('<form method="POST" action="'.route('doctor.records.destroy', $record).'"', $html);
        }
    }

    #[Group('M4-REL-2')]
    public function test_a_delete_cannot_be_triggered_by_simply_opening_a_link(): void
    {
        $record = MedicalRecord::factory()->create(['doctor_id' => $this->doctor->id, 'patient_id' => $this->patient->id]);

        $this->actingAs($this->doctor)->get("/doctor/records/{$record->id}/delete")->assertNotFound();
        $this->actingAs($this->doctor)->post("/doctor/records/{$record->id}")->assertStatus(405);

        $this->assertNotSoftDeleted($record);
    }

    #[Group('M4-REL-2'), Group('M4-REL-4')]
    public function test_one_hundred_deletes_lose_no_data_and_every_record_can_be_restored(): void
    {
        $records = MedicalRecord::factory()->count(100)->create(['doctor_id' => $this->doctor->id, 'patient_id' => $this->patient->id]);

        // Ten of them have a stored file, to prove files survive too.
        $files = [];
        foreach ($records->take(10) as $record) {
            $path = "medical-records/{$record->id}/file.pdf";
            Storage::disk('local')->put($path, 'contents');
            MedicalRecordAttachment::factory()->create(['medical_record_id' => $record->id, 'path' => $path]);
            $files[] = $path;
        }

        $failures = 0;
        foreach ($records as $record) {
            $status = $this->actingAs($this->doctor)->delete("/doctor/records/{$record->id}")->status();
            $failures += $status === 302 ? 0 : 1;
        }

        $this->assertSame(0, $failures, 'No delete request may fail.');
        $this->assertSame(0, MedicalRecord::count(), 'Deleted records are hidden.');
        $this->assertSame(100, MedicalRecord::onlyTrashed()->count(), 'No row was physically lost.');
        $this->assertSame(10, MedicalRecordAttachment::count(), 'Attachment rows remain.');
        foreach ($files as $path) {
            Storage::disk('local')->assertExists($path);
        }

        MedicalRecord::onlyTrashed()->get()->each->restore();
        $this->assertSame(100, MedicalRecord::count());
    }

    #[Group('M4-REL-2')]
    public function test_another_doctor_cannot_delete_a_record(): void
    {
        $record = MedicalRecord::factory()->create(['doctor_id' => $this->doctor->id, 'patient_id' => $this->patient->id]);

        foreach ([$this->doctor('Dr. Other'), $this->admin(), $this->patient] as $intruder) {
            $this->actingAs($intruder)->delete("/doctor/records/{$record->id}")->assertForbidden();
        }

        $this->assertNotSoftDeleted($record);
    }

    #[Group('M4-REL-3')]
    public function test_a_failure_while_saving_leaves_no_record_and_no_orphan_file(): void
    {
        $appointment = $this->startedAppointment();

        // Make the second step of the save fail after the file is already on disk.
        MedicalRecordAttachment::creating(fn () => throw new \RuntimeException('database went away'));

        $this->actingAs($this->doctor)->post('/doctor/records', [
            'appointment_id' => $appointment->id,
            'visit_date' => today()->subDay()->toDateString(),
            'chief_complaint' => 'Cough',
            'diagnosis' => 'Flu',
            'condition' => 'stable',
            'attachments' => [UploadedFile::fake()->create('lab.pdf', 20, 'application/pdf')],
        ]);

        $this->assertSame(0, MedicalRecord::withTrashed()->count(), 'The record was rolled back.');
        $this->assertSame(0, MedicalRecordAttachment::count());
        $this->assertSame([], Storage::disk('local')->allFiles(), 'The uploaded file was cleaned up.');
        $this->assertSame('booked', $appointment->fresh()->status->value, 'The appointment was not marked completed.');
    }

    #[Group('M4-REL-4')]
    public function test_one_hundred_repeated_reads_all_succeed(): void
    {
        MedicalRecord::factory()->count(20)->create(['doctor_id' => $this->doctor->id, 'patient_id' => $this->patient->id]);
        $this->actingAs($this->doctor);

        $failed = 0;
        for ($i = 0; $i < 100; $i++) {
            $failed += $this->get('/doctor/records')->status() === 200 ? 0 : 1;
        }

        // The report quotes 0 failed requests, which gives an error rate of 0%.
        $this->assertSame(0, $failed);
    }

    private function startedAppointment(): Appointment
    {
        $slot = RosterSlot::factory()->occupied()->create([
            'doctor_id' => $this->doctor->id,
            'slot_date' => today()->subDay()->toDateString(),
            'start_time' => '10:00',
        ]);

        return Appointment::factory()->create(['roster_slot_id' => $slot->id, 'patient_id' => $this->patient->id]);
    }
}
