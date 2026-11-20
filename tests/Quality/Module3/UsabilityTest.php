<?php

namespace Tests\Quality\Module3;

use App\Models\RosterSlot;
use PHPUnit\Framework\Attributes\Group;
use Tests\Quality\QualityTestCase;

/**
 * Module 3, Usability: TC-001 to TC-004.
 */
class UsabilityTest extends QualityTestCase
{
    private function payload(int $doctorId, array $overrides = []): array
    {
        return array_merge([
            'doctor_id' => $doctorId,
            'slot_date' => today()->addDays(2)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '09:30',
            'status' => 'available',
            'notes' => 'Room 101',
        ], $overrides);
    }

    #[Group('TC-001'), Group('SRS-REQ-301'), Group('SRS-REQ-302')]
    public function test_tc001_admin_creates_a_slot_and_sees_it_in_the_list_with_a_success_message(): void
    {
        $doctor = $this->doctor('Dr. Aisyah');

        $this->actingAs($this->admin())
            ->followingRedirects()
            ->post('/admin/rosters', $this->payload($doctor->id))
            ->assertOk()
            ->assertSee('Slot created.')
            ->assertSee('Dr. Aisyah')
            ->assertSee(today()->addDays(2)->format('d M Y'))
            ->assertSee('09:00 - 09:30')
            ->assertSee('Available')
            ->assertSee('Room 101');

        $this->assertSame(1, RosterSlot::count());
    }

    #[Group('TC-002'), Group('SRS-REQ-302'), Group('SRS-REQ-303'), Group('SRS-REQ-306')]
    public function test_tc002_list_view_and_calendar_view_show_the_same_slots(): void
    {
        $doctor = $this->doctor('Dr. Aisyah');
        $date = today()->addDays(2)->toDateString();
        $free = RosterSlot::factory()->create(['doctor_id' => $doctor->id, 'slot_date' => $date, 'start_time' => '09:00']);
        $taken = RosterSlot::factory()->occupied()->create(['doctor_id' => $doctor->id, 'slot_date' => $date, 'start_time' => '10:00']);
        $admin = $this->admin();

        $list = $this->actingAs($admin)->get('/admin/rosters')->assertOk();
        $calendar = $this->actingAs($admin)->get("/admin/rosters/calendar?week={$date}")->assertOk();

        // Both views must agree on code, doctor, time range and status.
        foreach ([$free, $taken] as $slot) {
            foreach ([$list, $calendar] as $view) {
                $view->assertSee($slot->time_range)->assertSee('Dr. Aisyah');
            }
        }
        $list->assertSee($free->slot_code)->assertSee($taken->slot_code);
        $calendar->assertSee($free->slot_code)->assertSee($taken->slot_code);
        $list->assertSee('Occupied');
        $calendar->assertSee('Occupied');

        // Switching between the views is one click each way.
        $list->assertSee(route('admin.rosters.calendar'), false);
        $calendar->assertSee(route('admin.rosters.index'), false);
    }

    #[Group('TC-003'), Group('SRS-REQ-304')]
    public function test_tc003_admin_edits_a_slot_and_only_that_slot_changes(): void
    {
        $doctor = $this->doctor('Dr. Aisyah');
        $target = RosterSlot::factory()->create(['doctor_id' => $doctor->id, 'slot_date' => today()->addDays(2)->toDateString(), 'start_time' => '09:00']);
        $other = RosterSlot::factory()->create(['doctor_id' => $doctor->id, 'slot_date' => today()->addDays(2)->toDateString(), 'start_time' => '14:00', 'notes' => 'Untouched']);

        $this->actingAs($this->admin())->followingRedirects()
            ->put("/admin/rosters/{$target->id}", $this->payload($doctor->id, ['start_time' => '10:00', 'end_time' => '10:45', 'notes' => 'Moved to later']))
            ->assertSee('Slot updated.')
            ->assertSee('10:00 - 10:45')
            ->assertSee('Moved to later');

        $this->assertSame('Untouched', $other->fresh()->notes);
        $this->assertSame('14:00', $other->fresh()->start_time_label);
    }

    #[Group('TC-004'), Group('SRS-REQ-308')]
    public function test_tc004_admin_searches_by_doctor_name_and_sorts_the_list(): void
    {
        $date = today()->addDays(2)->toDateString();
        $zul = RosterSlot::factory()->create(['doctor_id' => $this->doctor('Dr. Zulkifli')->id, 'slot_date' => $date]);
        $aisyah = RosterSlot::factory()->create(['doctor_id' => $this->doctor('Dr. Aisyah')->id, 'slot_date' => $date]);
        $admin = $this->admin();

        // Search narrows the list, clearing it brings everything back.
        $this->actingAs($admin)->get('/admin/rosters?search=aisyah')
            ->assertSee($aisyah->slot_code)->assertDontSee($zul->slot_code);
        $this->actingAs($admin)->get('/admin/rosters?search=')
            ->assertSee($aisyah->slot_code)->assertSee($zul->slot_code);

        // Sort options from the report: Latest Added and Doctor A-Z.
        $order = fn (string $sort) => $this->actingAs($admin)->get("/admin/rosters?sort={$sort}")->viewData('slots')->pluck('id')->all();

        $this->assertSame([$aisyah->id, $zul->id], $order('latest'));
        $this->assertSame([$aisyah->id, $zul->id], $order('doctor_az'));
    }
}
