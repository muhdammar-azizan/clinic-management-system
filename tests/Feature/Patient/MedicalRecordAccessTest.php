<?php

namespace Tests\Feature\Patient;

use App\Enums\Role;
use App\Models\MedicalRecord;
use App\Models\MedicalRecordAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MedicalRecordAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $patient;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->patient = User::factory()->create();
    }

    private function record(?User $patient = null, array $attributes = []): MedicalRecord
    {
        return MedicalRecord::factory()->create(array_merge(['patient_id' => ($patient ?? $this->patient)->id], $attributes));
    }

    private function attachment(MedicalRecord $record, string $type, string $name): MedicalRecordAttachment
    {
        $path = "medical-records/{$record->id}/".uniqid().'.pdf';
        Storage::disk('local')->put($path, 'file contents');

        return MedicalRecordAttachment::factory()->create([
            'medical_record_id' => $record->id, 'type' => $type, 'path' => $path, 'original_name' => $name,
        ]);
    }

    public function test_only_patients_can_open_the_pages(): void
    {
        $record = $this->record();

        $this->get('/patient/records')->assertRedirect('/login');

        foreach ([Role::Doctor, Role::Admin] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->actingAs($user)->get('/patient/records')->assertForbidden();
            $this->actingAs($user)->get("/patient/records/{$record->id}")->assertForbidden();
        }
    }

    public function test_patient_sees_only_their_own_records_newest_first(): void
    {
        $old = $this->record(null, ['visit_date' => now()->subDays(20)->toDateString()]);
        $new = $this->record(null, ['visit_date' => now()->subDay()->toDateString()]);
        $someoneElse = $this->record(User::factory()->create());

        $response = $this->actingAs($this->patient)->get('/patient/records')->assertOk();

        $this->assertSame([$new->id, $old->id], $response->viewData('records')->pluck('id')->all());
        $response->assertDontSee($someoneElse->record_code);
    }

    public function test_empty_state(): void
    {
        $this->actingAs($this->patient)->get('/patient/records')->assertSee('You have no medical records yet.');
    }

    public function test_patient_can_read_the_full_record(): void
    {
        $record = $this->record(null, [
            'diagnosis' => 'Seasonal flu',
            'treatment_plan' => 'Rest and fluids',
            'prescription' => 'Paracetamol 500mg',
        ]);

        $this->actingAs($this->patient)->get("/patient/records/{$record->id}")
            ->assertOk()
            ->assertSee('Seasonal flu')
            ->assertSee('Rest and fluids')
            ->assertSee('Paracetamol 500mg')
            ->assertSee($record->doctor->name);
    }

    public function test_patient_cannot_open_someone_elses_record(): void
    {
        $other = $this->record(User::factory()->create());

        $this->actingAs($this->patient)->get("/patient/records/{$other->id}")->assertForbidden();
    }

    public function test_deleted_records_are_not_found(): void
    {
        $record = $this->record();
        $record->delete();

        $this->actingAs($this->patient)->get("/patient/records/{$record->id}")->assertNotFound();
        $this->actingAs($this->patient)->get('/patient/records')->assertDontSee($record->record_code);
    }

    public function test_only_prescription_files_are_listed_and_downloadable(): void
    {
        $record = $this->record();
        $prescription = $this->attachment($record, 'prescription', 'rx.pdf');
        $lab = $this->attachment($record, 'other', 'lab-results.pdf');

        $this->actingAs($this->patient)->get("/patient/records/{$record->id}")
            ->assertSee('rx.pdf')
            ->assertDontSee('lab-results.pdf');

        $this->actingAs($this->patient)->get("/patient/records/attachments/{$prescription->id}")
            ->assertOk()->assertDownload('rx.pdf');

        $this->actingAs($this->patient)->get("/patient/records/attachments/{$lab->id}")->assertForbidden();
    }

    public function test_patient_cannot_download_someone_elses_prescription(): void
    {
        $other = $this->record(User::factory()->create());
        $prescription = $this->attachment($other, 'prescription', 'rx.pdf');

        $this->actingAs($this->patient)->get("/patient/records/attachments/{$prescription->id}")->assertForbidden();
    }

    public function test_missing_file_returns_404(): void
    {
        $prescription = $this->attachment($this->record(), 'prescription', 'rx.pdf');
        Storage::disk('local')->delete($prescription->path);

        $this->actingAs($this->patient)->get("/patient/records/attachments/{$prescription->id}")->assertNotFound();
    }

    public function test_the_pages_are_read_only(): void
    {
        $record = $this->record();

        $this->actingAs($this->patient)->get("/patient/records/{$record->id}")
            ->assertDontSee('/edit', false)
            ->assertDontSee('confirm-delete', false);

        $this->actingAs($this->patient)->put("/patient/records/{$record->id}", ['diagnosis' => 'x'])->assertStatus(405);
        $this->actingAs($this->patient)->delete("/patient/records/{$record->id}")->assertStatus(405);
        $this->actingAs($this->patient)->put("/doctor/records/{$record->id}", [])->assertForbidden();
    }

    public function test_menu_shows_my_records_for_patients(): void
    {
        $this->actingAs($this->patient)->get('/patient/dashboard')->assertSee(route('patient.records.index'), false);
    }
}
