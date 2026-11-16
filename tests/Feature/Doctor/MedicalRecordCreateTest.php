<?php

namespace Tests\Feature\Doctor;

use App\Enums\AppointmentStatus;
use App\Enums\ConditionStatus;
use App\Enums\Role;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\RosterSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MedicalRecordCreateTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->doctor = User::factory()->create(['role' => Role::Doctor]);
    }

    /**
     * An appointment on this doctor's slot. By default the visit has already started.
     */
    private function appointment(array $slot = [], string $state = 'booked', ?User $doctor = null): Appointment
    {
        $slot = RosterSlot::factory()->occupied()->create(array_merge([
            'doctor_id' => ($doctor ?? $this->doctor)->id,
            'slot_date' => now()->subDay()->toDateString(),
            'start_time' => '10:00',
        ], $slot));

        $factory = Appointment::factory();
        if ($state !== 'booked') {
            $factory = $factory->{$state}();
        }

        return $factory->create(['roster_slot_id' => $slot->id]);
    }

    private function payload(Appointment $appointment, array $overrides = []): array
    {
        return array_merge([
            'appointment_id' => $appointment->id,
            'visit_date' => now()->subDay()->toDateString(),
            'chief_complaint' => 'Persistent cough',
            'symptoms' => 'Dry cough for a week',
            'diagnosis' => 'Upper respiratory infection',
            'treatment_plan' => 'Rest and fluids',
            'prescription' => 'Paracetamol 500mg',
            'condition' => 'recovering',
            'follow_up_date' => now()->addWeek()->toDateString(),
        ], $overrides);
    }

    public function test_only_doctors_can_open_the_form(): void
    {
        $appointment = $this->appointment();

        $this->get('/doctor/records/create?appointment='.$appointment->id)->assertRedirect('/login');

        foreach ([Role::Patient, Role::Admin] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get('/doctor/records/create?appointment='.$appointment->id)->assertForbidden();
        }
    }

    public function test_form_shows_the_three_steps_with_patient_context(): void
    {
        $appointment = $this->appointment();

        $this->actingAs($this->doctor)->get('/doctor/records/create?appointment='.$appointment->id)
            ->assertOk()
            ->assertSee($appointment->patient->name)
            ->assertSee('Visit details')
            ->assertSee('Clinical assessment')
            ->assertSee('Attachments')
            ->assertSee('enctype="multipart/form-data"', false);
    }

    public function test_doctor_can_save_a_record_with_attachments(): void
    {
        $appointment = $this->appointment();

        $this->actingAs($this->doctor)->post('/doctor/records', $this->payload($appointment) + [
            'prescription_files' => [UploadedFile::fake()->create('rx.pdf', 100, 'application/pdf')],
            'attachments' => [
                UploadedFile::fake()->create('lab.pdf', 200, 'application/pdf'),
                UploadedFile::fake()->image('xray.png'),
            ],
        ])->assertRedirect('/doctor/appointments')->assertSessionHas('status');

        $record = MedicalRecord::with('attachments')->first();
        $this->assertSame($appointment->patient_id, $record->patient_id);
        $this->assertSame($this->doctor->id, $record->doctor_id);
        $this->assertSame($appointment->id, $record->appointment_id);
        $this->assertSame(ConditionStatus::Recovering, $record->condition);
        $this->assertCount(3, $record->attachments);
        $this->assertSame(1, $record->attachments->where('type', 'prescription')->count());
        $this->assertSame('rx.pdf', $record->attachments->firstWhere('type', 'prescription')->original_name);

        foreach ($record->attachments as $attachment) {
            Storage::disk('local')->assertExists($attachment->path);
        }
    }

    public function test_attachments_are_optional(): void
    {
        $appointment = $this->appointment();

        $this->actingAs($this->doctor)->post('/doctor/records', $this->payload($appointment))->assertSessionHasNoErrors();

        $this->assertSame(0, MedicalRecord::first()->attachments()->count());
    }

    public function test_saving_a_record_completes_a_booked_appointment(): void
    {
        $appointment = $this->appointment();

        $this->actingAs($this->doctor)->post('/doctor/records', $this->payload($appointment));

        $this->assertSame(AppointmentStatus::Completed, $appointment->fresh()->status);
    }

    public function test_a_completed_appointment_can_also_get_a_record(): void
    {
        $appointment = $this->appointment([], 'completed');

        $this->actingAs($this->doctor)->post('/doctor/records', $this->payload($appointment))->assertSessionHas('status');

        $this->assertSame(1, MedicalRecord::count());
    }

    public function test_only_one_record_per_appointment(): void
    {
        $appointment = $this->appointment();
        $this->actingAs($this->doctor)->post('/doctor/records', $this->payload($appointment));

        $this->actingAs($this->doctor)->post('/doctor/records', $this->payload($appointment))->assertSessionHas('error');
        $this->actingAs($this->doctor)->get('/doctor/records/create?appointment='.$appointment->id)->assertRedirect('/doctor/appointments');

        $this->assertSame(1, MedicalRecord::count());
    }

    public function test_no_record_before_the_visit_or_for_cancelled_appointments(): void
    {
        $future = $this->appointment(['slot_date' => now()->addDays(2)->toDateString()]);
        $cancelled = $this->appointment(['start_time' => '11:00'], 'cancelled');

        foreach ([$future, $cancelled] as $appointment) {
            $this->actingAs($this->doctor)->post('/doctor/records', $this->payload($appointment))->assertSessionHas('error');
        }

        $this->assertSame(0, MedicalRecord::count());
    }

    public function test_doctor_cannot_write_a_record_for_another_doctors_patient(): void
    {
        $other = User::factory()->create(['role' => Role::Doctor]);
        $appointment = $this->appointment([], 'booked', $other);

        $this->actingAs($this->doctor)->get('/doctor/records/create?appointment='.$appointment->id)->assertForbidden();
        $this->actingAs($this->doctor)->post('/doctor/records', $this->payload($appointment))->assertForbidden();

        $this->assertSame(0, MedicalRecord::count());
    }

    public function test_missing_appointment_returns_404(): void
    {
        $this->actingAs($this->doctor)->get('/doctor/records/create')->assertNotFound();
    }

    public function test_validation_rules(): void
    {
        $appointment = $this->appointment();

        $this->actingAs($this->doctor)->post('/doctor/records', $this->payload($appointment, [
            'visit_date' => now()->addDay()->toDateString(),
            'chief_complaint' => '',
            'diagnosis' => '',
            'condition' => 'unknown',
            'follow_up_date' => now()->subWeek()->toDateString(),
        ]))->assertSessionHasErrors(['visit_date', 'chief_complaint', 'diagnosis', 'condition', 'follow_up_date']);

        $this->assertSame(0, MedicalRecord::count());
    }

    public function test_file_type_size_and_count_are_limited(): void
    {
        $appointment = $this->appointment();

        $this->actingAs($this->doctor)->post('/doctor/records', $this->payload($appointment) + [
            'prescription_files' => [UploadedFile::fake()->create('virus.exe', 10)],
            'attachments' => [UploadedFile::fake()->create('huge.pdf', 6000, 'application/pdf')],
        ])->assertSessionHasErrors(['prescription_files.0', 'attachments.0']);

        $this->actingAs($this->doctor)->post('/doctor/records', $this->payload($appointment) + [
            'attachments' => array_map(fn ($i) => UploadedFile::fake()->create("f{$i}.pdf", 10, 'application/pdf'), range(1, 6)),
        ])->assertSessionHasErrors('attachments');

        $this->assertSame(0, MedicalRecord::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_files_go_to_the_private_disk_not_the_public_one(): void
    {
        $appointment = $this->appointment();

        $this->actingAs($this->doctor)->post('/doctor/records', $this->payload($appointment) + [
            'attachments' => [UploadedFile::fake()->create('lab.pdf', 10, 'application/pdf')],
        ]);

        $path = MedicalRecord::first()->attachments->first()->path;
        $this->assertStringStartsWith('medical-records/', $path);
        $this->assertFileDoesNotExist(public_path('storage/'.$path));
    }

    public function test_appointment_page_links_to_the_form_only_when_allowed(): void
    {
        $ready = $this->appointment();
        $future = $this->appointment(['slot_date' => now()->addDays(2)->toDateString(), 'start_time' => '11:00']);

        $this->actingAs($this->doctor)->get("/doctor/appointments/{$ready->id}/edit")->assertSee('Write medical record');
        $this->actingAs($this->doctor)->get("/doctor/appointments/{$future->id}/edit")->assertDontSee('Write medical record');

        MedicalRecord::factory()->create([
            'patient_id' => $ready->patient_id,
            'doctor_id' => $this->doctor->id,
            'appointment_id' => $ready->id,
        ]);
        $this->actingAs($this->doctor)->get("/doctor/appointments/{$ready->id}/edit")
            ->assertDontSee('Write medical record')
            ->assertSee('A medical record has been written');
    }
}
