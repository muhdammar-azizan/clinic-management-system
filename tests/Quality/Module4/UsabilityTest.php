<?php

namespace Tests\Quality\Module4;

use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\RosterSlot;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use Tests\Quality\QualityTestCase;

/**
 * Module 4 (Fatin's report), Usability. The report also times a first-time doctor (target 2 minutes).
 * That part needs a person, so it is done by hand and recorded in QUALITY.md.
 */
class UsabilityTest extends QualityTestCase
{
    private User $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctor = $this->doctor();
    }

    private int $slotCounter = 0;

    private function appointment(string $reason = null): Appointment
    {
        // Each appointment needs its own start time, because one doctor cannot have two slots at once.
        $n = $this->slotCounter++;

        $slot = RosterSlot::factory()->occupied()->create([
            'doctor_id' => $this->doctor->id,
            'slot_date' => today()->subDay()->toDateString(),
            'start_time' => sprintf('%02d:%02d', 6 + intdiv($n, 2), ($n % 2) * 30),
        ]);

        return Appointment::factory()->create(['roster_slot_id' => $slot->id, 'reason' => $reason ?? 'Persistent cough']);
    }

    private function payload(Appointment $appointment, array $overrides = []): array
    {
        return array_merge([
            'appointment_id' => $appointment->id,
            'visit_date' => today()->subDay()->toDateString(),
            'chief_complaint' => 'Persistent cough',
            'symptoms' => 'Dry cough for a week',
            'diagnosis' => 'Upper respiratory infection',
            'treatment_plan' => 'Rest and fluids',
            'prescription' => 'Paracetamol 500mg',
            'condition' => 'recovering',
            'follow_up_date' => today()->addWeek()->toDateString(),
        ], $overrides);
    }

    #[Group('M4-USE-1')]
    public function test_every_form_field_has_a_clear_label_and_required_ones_are_marked(): void
    {
        $appointment = $this->appointment();

        $html = $this->actingAs($this->doctor)->get('/doctor/records/create?appointment='.$appointment->id)->assertOk()->getContent();

        foreach (['Visit date', 'Chief complaint', 'Symptoms', 'Diagnosis', 'Treatment plan', 'Prescription', 'Patient condition', 'Follow-up date'] as $label) {
            $this->assertMatchesRegularExpression('/<label[^>]*>\s*'.preg_quote($label, '/').'/', $html, "Missing label: {$label}");
        }

        // Required fields carry the browser's required attribute and a visible asterisk.
        foreach (['visit_date', 'chief_complaint', 'diagnosis', 'condition'] as $field) {
            $this->assertMatchesRegularExpression('/<(input|textarea|select)[^>]*id="'.$field.'"[^>]*required/', $html, "{$field} is not required in the browser");
        }
        $this->assertGreaterThanOrEqual(4, substr_count($html, 'text-danger"> *'));

        // Optional fields are not forced.
        foreach (['symptoms', 'treatment_plan', 'prescription', 'follow_up_date'] as $field) {
            $this->assertDoesNotMatchRegularExpression('/<(input|textarea|select)[^>]*id="'.$field.'"[^>]*required/', $html);
        }
    }

    #[Group('M4-USE-1')]
    public function test_the_three_steps_are_shown_and_the_form_is_prefilled_from_the_appointment(): void
    {
        $appointment = $this->appointment('Headache since Monday');

        $this->actingAs($this->doctor)->get('/doctor/records/create?appointment='.$appointment->id)
            ->assertSee('Visit details')
            ->assertSee('Clinical assessment')
            ->assertSee('Attachments')
            ->assertSee('value="Headache since Monday"', false)
            ->assertSee('value="'.$appointment->slot->slot_date->toDateString().'"', false);
    }

    #[Group('M4-USE-2')]
    public function test_a_missing_required_field_gets_a_specific_message_and_nothing_typed_is_lost(): void
    {
        $appointment = $this->appointment();
        $form = '/doctor/records/create?appointment='.$appointment->id;

        $this->actingAs($this->doctor)->from($form)
            ->followingRedirects()
            ->post('/doctor/records', $this->payload($appointment, ['diagnosis' => '', 'chief_complaint' => 'Sore throat']))
            ->assertSee('The diagnosis field is required.')
            ->assertSee('value="Sore throat"', false)   // what the doctor typed is kept
            ->assertSee('step: 2', false);              // the form reopens on the step with the error

        $this->assertSame(0, MedicalRecord::count());
    }

    #[Group('M4-USE-3')]
    public function test_saving_shows_a_clear_success_message(): void
    {
        $appointment = $this->appointment();

        $this->actingAs($this->doctor)->followingRedirects()
            ->post('/doctor/records', $this->payload($appointment))
            ->assertSee('Medical record saved.');
    }

    #[Group('M4-USE-3')]
    public function test_the_patient_sees_the_saved_record_on_their_own_page(): void
    {
        $appointment = $this->appointment();
        $this->actingAs($this->doctor)->post('/doctor/records', $this->payload($appointment));

        $this->actingAs($appointment->patient)->get('/patient/records')
            ->assertSee('Upper respiratory infection')
            ->assertSee('Recovering');
    }

    #[Group('M4-USE-4')]
    public function test_data_accurate_rate_is_one_hundred_percent_for_twenty_records(): void
    {
        $conditions = ['stable', 'recovering', 'critical'];
        $fields = ['visit_date', 'chief_complaint', 'symptoms', 'diagnosis', 'treatment_plan', 'prescription', 'condition', 'follow_up_date'];
        $entered = 0;
        $savedPerfectly = 0;

        for ($i = 1; $i <= 20; $i++) {
            $appointment = $this->appointment();
            $input = $this->payload($appointment, [
                'visit_date' => today()->subDays($i % 5)->toDateString(),
                'chief_complaint' => "Complaint number {$i}",
                'symptoms' => "Symptoms line one {$i}\nSymptoms line two",
                'diagnosis' => "Diagnosis {$i} with punctuation, (brackets) & \"quotes\"",
                'treatment_plan' => "Plan {$i}",
                'prescription' => "Medicine {$i} 250mg twice daily",
                'condition' => $conditions[$i % 3],
                'follow_up_date' => today()->addDays($i)->toDateString(),
            ]);

            $this->actingAs($this->doctor)->post('/doctor/records', $input)->assertSessionHasNoErrors();

            $record = MedicalRecord::where('appointment_id', $appointment->id)->firstOrFail();

            foreach ($fields as $field) {
                $entered++;
                $stored = $record->{$field};
                $stored = $stored instanceof \BackedEnum ? $stored->value : ($stored instanceof \DateTimeInterface ? $stored->format('Y-m-d') : $stored);
                $savedPerfectly += $stored === $input[$field] ? 1 : 0;
            }
        }

        // Data Accurate Rate = values saved perfectly / values entered x 100
        $rate = round($savedPerfectly / $entered * 100, 2);

        $this->assertSame(160, $entered);
        $this->assertSame(100.0, $rate);
    }
}
