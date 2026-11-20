<?php

namespace Tests\Feature\Doctor;

use App\Enums\Role;
use App\Models\MedicalRecord;
use App\Models\MedicalRecordAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MedicalRecordManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->doctor = User::factory()->create(['role' => Role::Doctor]);
    }

    private function record(array $attributes = [], ?User $doctor = null): MedicalRecord
    {
        return MedicalRecord::factory()->create(array_merge(['doctor_id' => ($doctor ?? $this->doctor)->id], $attributes));
    }

    private function attachment(MedicalRecord $record, string $type = 'other', string $name = 'report.pdf'): MedicalRecordAttachment
    {
        $path = "medical-records/{$record->id}/".uniqid().'.pdf';
        Storage::disk('local')->put($path, 'file contents');

        return MedicalRecordAttachment::factory()->create([
            'medical_record_id' => $record->id,
            'type' => $type,
            'path' => $path,
            'original_name' => $name,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'visit_date' => now()->subDay()->toDateString(),
            'chief_complaint' => 'Updated complaint',
            'diagnosis' => 'Updated diagnosis',
            'condition' => 'critical',
        ], $overrides);
    }

    public function test_only_doctors_can_open_the_records(): void
    {
        $this->get('/doctor/records')->assertRedirect('/login');

        foreach ([Role::Patient, Role::Admin] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))->get('/doctor/records')->assertForbidden();
        }
    }

    public function test_doctor_sees_only_their_own_records_newest_first(): void
    {
        $old = $this->record(['visit_date' => now()->subDays(10)->toDateString()]);
        $new = $this->record(['visit_date' => now()->subDay()->toDateString()]);
        $other = $this->record([], User::factory()->create(['role' => Role::Doctor]));

        $response = $this->actingAs($this->doctor)->get('/doctor/records')->assertOk();

        $this->assertSame([$new->id, $old->id], $response->viewData('records')->pluck('id')->all());
        $response->assertDontSee($other->record_code);
    }

    public function test_list_search_and_filters(): void
    {
        // Fixed names and emails: random ones can contain the digit searched for below.
        $match = $this->record([
            'patient_id' => User::factory()->create(['name' => 'Aisyah Rahman', 'email' => 'aisyah@example.test'])->id,
            'diagnosis' => 'Acute bronchitis', 'condition' => 'critical', 'visit_date' => '2026-12-10',
        ]);
        $other = $this->record([
            'patient_id' => User::factory()->create(['name' => 'Daniel Lim', 'email' => 'daniel@example.test'])->id,
            'diagnosis' => 'Sprained ankle', 'condition' => 'stable', 'visit_date' => '2026-11-01',
        ]);

        $get = fn (string $q) => $this->actingAs($this->doctor)->get('/doctor/records?'.$q);

        $get('search=bronchitis')->assertSee($match->record_code)->assertDontSee($other->record_code);
        $get('search='.urlencode($match->patient->email))->assertSee($match->record_code)->assertDontSee($other->record_code);
        $get('search='.$match->id)->assertSee($match->record_code)->assertDontSee($other->record_code);
        $get('condition=critical')->assertSee($match->record_code)->assertDontSee($other->record_code);
        $get('from=2026-12-01&to=2026-12-31')->assertSee($match->record_code)->assertDontSee($other->record_code);
        $get('condition=maybe')->assertSessionHasErrors('condition');
        $get('search=zzz-nothing')->assertSee('No records match your filters.');
    }

    public function test_doctor_can_view_a_record_with_its_files(): void
    {
        $record = $this->record(['diagnosis' => 'Seasonal flu']);
        $this->attachment($record, 'prescription', 'rx.pdf');

        $this->actingAs($this->doctor)->get("/doctor/records/{$record->id}")
            ->assertOk()
            ->assertSee('Seasonal flu')
            ->assertSee('rx.pdf')
            ->assertSee('Prescription');
    }

    public function test_doctor_can_download_an_attachment(): void
    {
        $record = $this->record();
        $attachment = $this->attachment($record, 'other', 'lab.pdf');

        $this->actingAs($this->doctor)->get("/doctor/records/attachments/{$attachment->id}")
            ->assertOk()
            ->assertDownload('lab.pdf');
    }

    public function test_missing_file_returns_404(): void
    {
        $attachment = $this->attachment($this->record());
        Storage::disk('local')->delete($attachment->path);

        $this->actingAs($this->doctor)->get("/doctor/records/attachments/{$attachment->id}")->assertNotFound();
    }

    public function test_doctor_cannot_use_another_doctors_record_or_files(): void
    {
        $other = $this->record([], User::factory()->create(['role' => Role::Doctor]));
        $attachment = $this->attachment($other);

        foreach (["/doctor/records/{$other->id}", "/doctor/records/{$other->id}/edit", "/doctor/records/attachments/{$attachment->id}"] as $url) {
            $this->actingAs($this->doctor)->get($url)->assertForbidden();
        }

        $this->actingAs($this->doctor)->put("/doctor/records/{$other->id}", $this->payload())->assertForbidden();
        $this->actingAs($this->doctor)->delete("/doctor/records/{$other->id}")->assertForbidden();

        $this->assertNotSame('Updated diagnosis', $other->fresh()->diagnosis);
        $this->assertNotSoftDeleted($other);
    }

    public function test_doctor_can_edit_a_record(): void
    {
        $record = $this->record();

        $this->actingAs($this->doctor)->get("/doctor/records/{$record->id}/edit")->assertOk()->assertSee($record->diagnosis);

        $this->actingAs($this->doctor)->put("/doctor/records/{$record->id}", $this->payload())
            ->assertRedirect("/doctor/records/{$record->id}")->assertSessionHas('status');

        $record->refresh();
        $this->assertSame('Updated diagnosis', $record->diagnosis);
        $this->assertSame('critical', $record->condition->value);
        $this->assertSame($this->doctor->id, $record->doctor_id);
    }

    public function test_edit_validates_like_create(): void
    {
        $record = $this->record();

        $this->actingAs($this->doctor)->put("/doctor/records/{$record->id}", $this->payload([
            'diagnosis' => '',
            'visit_date' => now()->addDay()->toDateString(),
        ]))->assertSessionHasErrors(['diagnosis', 'visit_date']);
    }

    public function test_edit_can_add_files_and_remove_existing_ones(): void
    {
        $record = $this->record();
        $keep = $this->attachment($record, 'other', 'keep.pdf');
        $drop = $this->attachment($record, 'other', 'drop.pdf');

        $this->actingAs($this->doctor)->put("/doctor/records/{$record->id}", $this->payload() + [
            'remove_attachments' => [$drop->id],
            'prescription_files' => [UploadedFile::fake()->create('new-rx.pdf', 50, 'application/pdf')],
        ])->assertSessionHasNoErrors();

        $names = $record->attachments()->pluck('original_name')->all();
        $this->assertEqualsCanonicalizing(['keep.pdf', 'new-rx.pdf'], $names);
        Storage::disk('local')->assertExists($keep->path);
        Storage::disk('local')->assertMissing($drop->path);
    }

    public function test_remove_cannot_touch_another_records_attachment(): void
    {
        $record = $this->record();
        $foreign = $this->attachment($this->record(), 'other', 'foreign.pdf');

        $this->actingAs($this->doctor)->put("/doctor/records/{$record->id}", $this->payload() + [
            'remove_attachments' => [$foreign->id],
        ]);

        $this->assertDatabaseHas('medical_record_attachments', ['id' => $foreign->id]);
        Storage::disk('local')->assertExists($foreign->path);
    }

    public function test_delete_is_a_soft_delete_and_hides_the_record(): void
    {
        $record = $this->record();
        $attachment = $this->attachment($record);

        $this->actingAs($this->doctor)->delete("/doctor/records/{$record->id}")
            ->assertRedirect('/doctor/records')->assertSessionHas('status');

        $this->assertSoftDeleted($record);
        Storage::disk('local')->assertExists($attachment->path);
        $this->actingAs($this->doctor)->get('/doctor/records')->assertDontSee($record->record_code);
        $this->actingAs($this->doctor)->get("/doctor/records/{$record->id}")->assertNotFound();
    }

    public function test_menu_shows_medical_records_for_doctors(): void
    {
        $this->actingAs($this->doctor)->get('/doctor/dashboard')->assertSee(route('doctor.records.index'), false);
    }
}
