<?php

namespace Tests\Quality\Module3;

use App\Models\Appointment;
use App\Models\RosterSlot;
use Illuminate\Database\QueryException;
use PHPUnit\Framework\Attributes\Group;
use Tests\Quality\QualityTestCase;

/**
 * Module 3, Data Integrity: TC-008 to TC-010.
 */
class DataIntegrityTest extends QualityTestCase
{
    private function payload(int $doctorId, array $overrides = []): array
    {
        return array_merge([
            'doctor_id' => $doctorId,
            'slot_date' => today()->addDays(2)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '09:30',
            'status' => 'available',
        ], $overrides);
    }

    #[Group('TC-008'), Group('SRS-REQ-307')]
    public function test_tc008_a_duplicate_slot_is_refused_by_the_application_and_by_the_database(): void
    {
        $doctor = $this->doctor();
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/rosters', $this->payload($doctor->id))->assertSessionHasNoErrors();
        $this->actingAs($admin)->post('/admin/rosters', $this->payload($doctor->id))
            ->assertSessionHasErrors(['start_time' => 'This doctor already has a slot at that date and time.']);

        $this->assertSame(1, RosterSlot::count());

        // Defence in depth: even code that skips validation cannot store a duplicate.
        $this->expectException(QueryException::class);
        RosterSlot::create($this->payload($doctor->id));
    }

    #[Group('TC-008'), Group('SRS-REQ-307')]
    public function test_tc008_overlapping_ranges_of_one_doctor_are_refused(): void
    {
        $doctor = $this->doctor();
        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/rosters', $this->payload($doctor->id, ['end_time' => '10:00']));

        $this->actingAs($admin)->post('/admin/rosters', $this->payload($doctor->id, ['start_time' => '09:30', 'end_time' => '10:30']))
            ->assertSessionHasErrors('end_time');

        $this->assertSame(1, RosterSlot::count());
    }

    #[Group('TC-009'), Group('SRS-REQ-305')]
    public function test_tc009_delete_asks_for_confirmation_and_removes_the_slot_only_after_it(): void
    {
        $slot = RosterSlot::factory()->create(['slot_date' => today()->addDays(2)->toDateString()]);
        $admin = $this->admin();

        // The page offers Delete only through the confirmation dialog.
        $page = $this->actingAs($admin)->get('/admin/rosters')
            ->assertSee('Are you sure you want to delete this record?')
            ->assertSee('This action cannot be undone.')
            ->assertSee('confirm-delete', false)
            ->assertSee(route('admin.rosters.destroy', $slot), false);
        $this->assertStringNotContainsString('<form method="POST" action="'.route('admin.rosters.destroy', $slot).'"', $page->getContent());

        // Cancelling sends nothing, so the slot is untouched. Confirming sends the delete.
        $this->assertDatabaseHas('roster_slots', ['id' => $slot->id]);

        $this->actingAs($admin)->followingRedirects()->delete("/admin/rosters/{$slot->id}")
            ->assertSee('Slot deleted.')
            ->assertDontSee($slot->slot_code);

        $this->assertDatabaseMissing('roster_slots', ['id' => $slot->id]);
    }

    #[Group('TC-009'), Group('SRS-REQ-305')]
    public function test_tc009_a_slot_with_a_booked_appointment_cannot_be_deleted(): void
    {
        $slot = RosterSlot::factory()->occupied()->create(['slot_date' => today()->addDays(2)->toDateString()]);
        Appointment::factory()->create(['roster_slot_id' => $slot->id]);

        $this->actingAs($this->admin())->delete("/admin/rosters/{$slot->id}")->assertSessionHas('error');

        $this->assertDatabaseHas('roster_slots', ['id' => $slot->id]);
        $this->assertSame(1, Appointment::count());
    }

    #[Group('TC-010'), Group('SRS-REQ-307')]
    public function test_tc010_the_same_doctor_and_date_with_a_different_time_is_allowed(): void
    {
        $doctor = $this->doctor();
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/rosters', $this->payload($doctor->id))->assertSessionHasNoErrors();
        $this->actingAs($admin)->post('/admin/rosters', $this->payload($doctor->id, ['start_time' => '10:00', 'end_time' => '10:30']))->assertSessionHasNoErrors();

        // Another doctor may also use the very same time.
        $this->actingAs($admin)->post('/admin/rosters', $this->payload($this->doctor()->id))->assertSessionHasNoErrors();

        $this->assertSame(3, RosterSlot::count());
    }
}
