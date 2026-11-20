<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\RosterSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SRS-REQ-301 (end time) and SRS-REQ-307 (no two slots of one doctor overlap in time).
 */
class RosterSlotEndTimeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $doctor;

    private string $date;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => Role::Admin]);
        $this->doctor = User::factory()->create(['role' => Role::Doctor]);
        $this->date = today()->addDays(3)->toDateString();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'doctor_id' => $this->doctor->id,
            'slot_date' => $this->date,
            'start_time' => '09:00',
            'end_time' => '09:30',
            'status' => 'available',
        ], $overrides);
    }

    private function existing(string $start, ?string $end, ?User $doctor = null): RosterSlot
    {
        $slot = RosterSlot::factory()->create([
            'doctor_id' => ($doctor ?? $this->doctor)->id,
            'slot_date' => $this->date,
            'start_time' => $start,
        ]);
        $slot->forceFill(['end_time' => $end])->save();

        return $slot;
    }

    public function test_the_end_time_is_required_and_saved(): void
    {
        $this->actingAs($this->admin)->post('/admin/rosters', $this->payload(['end_time' => '']))
            ->assertSessionHasErrors('end_time');

        $this->actingAs($this->admin)->post('/admin/rosters', $this->payload())->assertSessionHasNoErrors();

        $slot = RosterSlot::first();
        $this->assertSame('09:30', $slot->end_time_label);
        $this->assertSame('09:00 - 09:30', $slot->time_range);
    }

    public function test_the_end_time_must_be_later_than_the_start_time(): void
    {
        foreach (['09:00', '08:30'] as $end) {
            $this->actingAs($this->admin)->post('/admin/rosters', $this->payload(['end_time' => $end]))
                ->assertSessionHasErrors(['end_time' => 'The end time must be later than the start time.']);
        }

        $this->assertSame(0, RosterSlot::count());
    }

    public function test_overlapping_slots_of_the_same_doctor_are_rejected(): void
    {
        $this->existing('09:00', '10:00');

        // Starts inside, ends inside, wraps around, and starts earlier but runs into it.
        foreach ([['09:15', '09:45'], ['09:30', '10:30'], ['08:30', '10:30'], ['08:30', '09:15']] as [$start, $end]) {
            $this->actingAs($this->admin)->post('/admin/rosters', $this->payload(['start_time' => $start, 'end_time' => $end]))
                ->assertSessionHasErrors('end_time');
        }

        $this->assertSame(1, RosterSlot::count());
    }

    public function test_back_to_back_slots_do_not_overlap(): void
    {
        $this->existing('09:00', '09:30');

        $this->actingAs($this->admin)->post('/admin/rosters', $this->payload(['start_time' => '09:30', 'end_time' => '10:00']))
            ->assertSessionHasNoErrors();
        $this->actingAs($this->admin)->post('/admin/rosters', $this->payload(['start_time' => '08:30', 'end_time' => '09:00']))
            ->assertSessionHasNoErrors();

        $this->assertSame(3, RosterSlot::count());
    }

    public function test_other_doctors_and_other_days_never_clash(): void
    {
        $this->existing('09:00', '10:00');
        $other = User::factory()->create(['role' => Role::Doctor]);

        $this->actingAs($this->admin)->post('/admin/rosters', $this->payload(['doctor_id' => $other->id]))->assertSessionHasNoErrors();
        $this->actingAs($this->admin)->post('/admin/rosters', $this->payload(['slot_date' => today()->addDays(4)->toDateString()]))->assertSessionHasNoErrors();
    }

    public function test_editing_a_slot_does_not_clash_with_itself(): void
    {
        $slot = $this->existing('09:00', '09:30');

        $this->actingAs($this->admin)->put("/admin/rosters/{$slot->id}", $this->payload(['end_time' => '10:00']))
            ->assertSessionHasNoErrors();

        $this->assertSame('10:00', $slot->fresh()->end_time_label);
    }

    public function test_editing_cannot_stretch_a_slot_into_its_neighbour(): void
    {
        $this->existing('09:30', '10:00');
        $slot = $this->existing('09:00', '09:30');

        $this->actingAs($this->admin)->put("/admin/rosters/{$slot->id}", $this->payload(['end_time' => '09:45']))
            ->assertSessionHasErrors('end_time');
    }

    public function test_an_old_slot_without_an_end_time_counts_as_an_instant(): void
    {
        $this->existing('09:15', null);

        // 09:00-09:30 covers 09:15, so it clashes. 09:30-10:00 does not.
        $this->actingAs($this->admin)->post('/admin/rosters', $this->payload(['start_time' => '09:00', 'end_time' => '09:30']))
            ->assertSessionHasErrors('end_time');
        $this->actingAs($this->admin)->post('/admin/rosters', $this->payload(['start_time' => '09:30', 'end_time' => '10:00']))
            ->assertSessionHasNoErrors();
    }

    public function test_an_old_slot_without_an_end_time_still_shows_only_its_start(): void
    {
        $slot = $this->existing('09:15', null);

        $this->assertNull($slot->end_time_label);
        $this->assertSame('09:15', $slot->time_range);
        $this->actingAs($this->admin)->get('/admin/rosters')->assertOk()->assertSee($slot->slot_code);
    }

    public function test_the_time_range_appears_in_every_view_that_lists_slots(): void
    {
        $slot = $this->existing('09:00', '09:45');
        $week = $this->date;

        $this->actingAs($this->admin)->get('/admin/rosters')->assertSee('09:00 - 09:45');
        $this->actingAs($this->admin)->get("/admin/rosters/calendar?week={$week}")->assertSee('09:00 - 09:45');
        $this->actingAs($this->admin)->get("/admin/rosters/{$slot->id}/edit")->assertSee('value="09:45"', false);
        $this->actingAs($this->doctor)->get("/doctor/schedule?week={$week}")->assertSee('09:00 - 09:45');
    }
}
