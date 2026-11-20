<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\RosterSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TC-005, TC-006 and TC-007 from the SQA report: a slot cannot be saved in the past.
 */
class RosterSlotTimeRuleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        // Fixed clock so the tests never depend on the real time of day.
        $this->travelTo(now()->setTime(15, 0));

        $this->admin = User::factory()->create(['role' => Role::Admin]);
        $this->doctor = User::factory()->create(['role' => Role::Doctor]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'doctor_id' => $this->doctor->id,
            'slot_date' => today()->toDateString(),
            'start_time' => '16:00',
            'end_time' => '16:30',
            'status' => 'available',
        ], $overrides);
    }

    public function test_tc005_a_past_date_is_rejected_with_the_report_message(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/rosters', $this->payload(['slot_date' => today()->subDay()->toDateString()]))
            ->assertSessionHasErrors(['slot_date' => 'Past schedule slots are not allowed. Please choose today or a future date.']);

        $this->assertSame(0, RosterSlot::count());
    }

    public function test_tc006_a_time_that_has_already_passed_today_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/rosters', $this->payload(['start_time' => '10:00', 'end_time' => '10:30']))
            ->assertSessionHasErrors('start_time');

        $this->assertSame(0, RosterSlot::count());
    }

    public function test_tc006_the_current_minute_is_also_rejected(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/rosters', $this->payload(['start_time' => '15:00', 'end_time' => '15:30']))
            ->assertSessionHasErrors('start_time');
    }

    public function test_tc007_today_with_a_later_time_is_accepted(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/rosters', $this->payload(['start_time' => '15:30', 'end_time' => '16:00']))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, RosterSlot::count());
    }

    public function test_an_early_time_on_a_future_date_is_accepted(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/rosters', $this->payload(['slot_date' => today()->addDay()->toDateString(), 'start_time' => '08:00', 'end_time' => '08:30']))
            ->assertSessionHasNoErrors();
    }

    public function test_update_cannot_move_a_slot_to_a_past_time(): void
    {
        $slot = RosterSlot::factory()->create([
            'doctor_id' => $this->doctor->id,
            'slot_date' => today()->addDays(2)->toDateString(),
            'start_time' => '09:00',
        ]);

        $this->actingAs($this->admin)
            ->put("/admin/rosters/{$slot->id}", $this->payload(['start_time' => '10:00', 'end_time' => '10:30']))
            ->assertSessionHasErrors('start_time');

        $this->actingAs($this->admin)
            ->put("/admin/rosters/{$slot->id}", $this->payload(['slot_date' => today()->subDay()->toDateString(), 'start_time' => '09:00', 'end_time' => '09:30']))
            ->assertSessionHasErrors('slot_date');

        $this->assertSame('09:00', $slot->fresh()->start_time_label);
    }

    public function test_update_may_keep_an_unchanged_past_slot_while_fixing_other_fields(): void
    {
        $slot = RosterSlot::factory()->create([
            'doctor_id' => $this->doctor->id,
            'slot_date' => today()->toDateString(),
            'start_time' => '09:00',
        ]);

        $this->actingAs($this->admin)
            ->put("/admin/rosters/{$slot->id}", $this->payload(['start_time' => '09:00', 'end_time' => '09:30', 'notes' => 'Fixed typo']))
            ->assertSessionHasNoErrors();

        $this->assertSame('Fixed typo', $slot->fresh()->notes);
    }

    public function test_malformed_input_gets_a_validation_error_not_a_crash(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/rosters', $this->payload(['slot_date' => '2026-13-45']))
            ->assertSessionHasErrors('slot_date');
    }
}
