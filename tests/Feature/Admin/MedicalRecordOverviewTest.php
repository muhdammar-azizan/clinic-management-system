<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\MedicalRecordAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedicalRecordOverviewTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => Role::Admin]);
    }

    private function search(string $query = '')
    {
        return $this->actingAs($this->admin)->get('/admin/records?'.$query);
    }

    public function test_only_admin_can_open_the_overview(): void
    {
        $this->get('/admin/records')->assertRedirect('/login');

        foreach ([Role::Patient, Role::Doctor] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))->get('/admin/records')->assertForbidden();
        }

        $this->search()->assertOk();
    }

    public function test_admin_sees_who_when_and_how_many_files_but_never_clinical_content(): void
    {
        $appointment = Appointment::factory()->completed()->create();
        $record = MedicalRecord::factory()->create([
            'patient_id' => $appointment->patient_id,
            'doctor_id' => $appointment->slot->doctor_id,
            'appointment_id' => $appointment->id,
            'chief_complaint' => 'Secret complaint text',
            'symptoms' => 'Secret symptoms text',
            'diagnosis' => 'Secret diagnosis text',
            'treatment_plan' => 'Secret treatment text',
            'prescription' => 'Secret prescription text',
            'condition' => 'critical',
        ]);
        MedicalRecordAttachment::factory()->create(['medical_record_id' => $record->id, 'original_name' => 'secret-lab.pdf']);

        $this->search()
            ->assertSee($record->record_code)
            ->assertSee($appointment->patient->name)
            ->assertSee($appointment->slot->doctor->name)
            ->assertSee($appointment->service->name)
            ->assertDontSee('Secret complaint text')
            ->assertDontSee('Secret symptoms text')
            ->assertDontSee('Secret diagnosis text')
            ->assertDontSee('Secret treatment text')
            ->assertDontSee('Secret prescription text')
            ->assertDontSee('secret-lab.pdf')
            ->assertDontSee('Critical');
    }

    public function test_diagnosis_is_not_searchable_by_admin(): void
    {
        $record = MedicalRecord::factory()->create(['diagnosis' => 'Rare tropical disease']);

        $this->search('search='.urlencode('tropical'))
            ->assertDontSee($record->record_code)
            ->assertSee('No records match your filters.');
    }

    public function test_search_and_filters(): void
    {
        // Fixed names and emails keep the search checks free of random faker collisions.
        $target = MedicalRecord::factory()->create([
            'visit_date' => '2026-12-10',
            'patient_id' => User::factory()->create(['name' => 'Aisyah Rahman', 'email' => 'aisyah@example.test'])->id,
            'doctor_id' => User::factory()->create(['name' => 'Dr. Zulkifli', 'role' => Role::Doctor, 'email' => 'zul@example.test'])->id,
        ]);
        $other = MedicalRecord::factory()->create([
            'visit_date' => '2026-11-01',
            'patient_id' => User::factory()->create(['name' => 'Daniel Lim', 'email' => 'daniel@example.test'])->id,
            'doctor_id' => User::factory()->create(['name' => 'Dr. Hamid', 'role' => Role::Doctor, 'email' => 'hamid@example.test'])->id,
        ]);

        $this->search('search=aisyah%40example.test')->assertSee($target->record_code)->assertDontSee($other->record_code);
        $this->search('search=Zulkifli')->assertSee($target->record_code)->assertDontSee($other->record_code);
        $this->search('search='.$target->id)->assertSee($target->record_code)->assertDontSee($other->record_code);
        $this->search('doctor='.$target->doctor_id)->assertSee($target->record_code)->assertDontSee($other->record_code);
        $this->search('from=2026-12-01&to=2026-12-31')->assertSee($target->record_code)->assertDontSee($other->record_code);
        $this->search('from=2026-12-20&to=2026-12-01')->assertSessionHasErrors('to');
    }

    public function test_deleted_records_are_not_listed(): void
    {
        $record = MedicalRecord::factory()->create();
        $record->delete();

        $this->search()->assertDontSee($record->record_code);
    }

    public function test_admin_cannot_open_or_change_records(): void
    {
        $record = MedicalRecord::factory()->create();

        $this->actingAs($this->admin)->get("/doctor/records/{$record->id}")->assertForbidden();
        $this->actingAs($this->admin)->get("/patient/records/{$record->id}")->assertForbidden();
        $this->actingAs($this->admin)->delete("/doctor/records/{$record->id}")->assertForbidden();
        $this->assertNotSoftDeleted($record);
    }

    public function test_the_overview_has_no_action_buttons(): void
    {
        MedicalRecord::factory()->create();

        $this->search()->assertDontSee('confirm-delete', false)->assertDontSee('/edit', false);
    }
}
