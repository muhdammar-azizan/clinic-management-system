<?php

namespace Tests\Quality\Module3;

use App\Models\RosterSlot;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use Tests\Quality\QualityTestCase;

/**
 * Module 3, Accuracy: TC-005 to TC-007. The clock is frozen at 15:00 so the result never depends on when the test runs.
 */
class AccuracyTest extends QualityTestCase
{
    private User $admin;

    private User $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now()->setTime(15, 0));
        $this->admin = $this->admin();
        $this->doctor = $this->doctor();
    }

    private function save(array $overrides)
    {
        return $this->actingAs($this->admin)->post('/admin/rosters', array_merge([
            'doctor_id' => $this->doctor->id,
            'slot_date' => today()->toDateString(),
            'start_time' => '16:00',
            'end_time' => '16:30',
            'status' => 'available',
        ], $overrides));
    }

    #[Group('TC-005'), Group('SRS-REQ-309'), Group('SRS-REQ-310')]
    public function test_tc005_a_slot_with_a_past_date_is_not_saved_and_the_message_matches_the_report(): void
    {
        $this->save(['slot_date' => today()->subDay()->toDateString()])
            ->assertSessionHasErrors(['slot_date' => 'Past schedule slots are not allowed. Please choose today or a future date.']);

        $this->assertSame(0, RosterSlot::count());
    }

    #[Group('TC-006'), Group('SRS-REQ-309'), Group('SRS-REQ-310')]
    public function test_tc006_a_start_time_that_has_passed_today_is_not_saved(): void
    {
        $this->save(['start_time' => '10:00', 'end_time' => '10:30'])->assertSessionHasErrors('start_time');
        $this->save(['start_time' => '15:00', 'end_time' => '15:30'])->assertSessionHasErrors('start_time');

        $this->assertSame(0, RosterSlot::count());
    }

    #[Group('TC-007'), Group('SRS-REQ-309'), Group('SRS-REQ-310')]
    public function test_tc007_today_with_a_future_start_time_is_saved_and_shown_correctly(): void
    {
        $this->save(['start_time' => '15:30', 'end_time' => '16:00'])->assertSessionHasNoErrors();

        $slot = RosterSlot::firstOrFail();
        $this->assertSame(today()->toDateString(), $slot->slot_date->toDateString());
        $this->assertSame('15:30', $slot->start_time_label);
        $this->assertSame('16:00', $slot->end_time_label);

        $this->actingAs($this->admin)->get('/admin/rosters')->assertSee('15:30 - 16:00');
    }

    #[Group('TC-005'), Group('TC-007'), Group('SRS-REQ-309')]
    public function test_patients_are_never_offered_a_slot_that_has_already_started(): void
    {
        $profile = \App\Models\DoctorProfile::factory()->create(['user_id' => $this->doctor->id]);
        $past = RosterSlot::factory()->create(['doctor_id' => $this->doctor->id, 'slot_date' => today()->toDateString(), 'start_time' => '10:00']);
        $later = RosterSlot::factory()->create(['doctor_id' => $this->doctor->id, 'slot_date' => today()->toDateString(), 'start_time' => '16:00']);

        $offered = $this->actingAs($this->patient())->get('/patient/book?service='.$profile->service_id)
            ->viewData('slotsByDay')->flatten()->pluck('id')->all();

        $this->assertSame([$later->id], $offered);
        $this->assertNotContains($past->id, $offered);
    }
}
