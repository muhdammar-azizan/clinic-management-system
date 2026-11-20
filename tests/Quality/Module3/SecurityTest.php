<?php

namespace Tests\Quality\Module3;

use App\Models\RosterSlot;
use PHPUnit\Framework\Attributes\Group;
use Tests\Quality\QualityTestCase;

/**
 * Module 3, Security: TC-011 to TC-013.
 */
class SecurityTest extends QualityTestCase
{
    /**
     * Every roster management request, as [method, url]. Slot-specific urls use the given id.
     *
     * @return list<array{string, string}>
     */
    private function managementRequests(int $slotId): array
    {
        return [
            ['get', '/admin/rosters'],
            ['get', '/admin/rosters/calendar'],
            ['get', '/admin/rosters/create'],
            ['get', '/admin/rosters/generate'],
            ['get', "/admin/rosters/{$slotId}/edit"],
            ['post', '/admin/rosters'],
            ['post', '/admin/rosters/generate'],
            ['put', "/admin/rosters/{$slotId}"],
            ['delete', "/admin/rosters/{$slotId}"],
        ];
    }

    #[Group('TC-011'), Group('SRS-REQ-311'), Group('SRS-REQ-312')]
    public function test_tc011_doctors_and_patients_are_blocked_from_every_roster_management_request(): void
    {
        $slot = RosterSlot::factory()->create();

        foreach ([$this->doctor(), $this->patient()] as $user) {
            foreach ($this->managementRequests($slot->id) as [$method, $url]) {
                $this->actingAs($user)->{$method}($url)->assertForbidden();
            }
        }

        $this->assertDatabaseHas('roster_slots', ['id' => $slot->id]);
        $this->assertSame(1, RosterSlot::count());
    }

    #[Group('TC-011'), Group('SRS-REQ-312')]
    public function test_tc011_visitors_who_are_not_signed_in_are_sent_to_the_login_page(): void
    {
        $slot = RosterSlot::factory()->create();

        foreach ($this->managementRequests($slot->id) as [$method, $url]) {
            $this->{$method}($url)->assertRedirect('/login');
        }

        $this->assertSame(1, RosterSlot::count());
    }

    #[Group('TC-011'), Group('SRS-REQ-311')]
    public function test_tc011_the_roster_menu_is_shown_to_admins_only(): void
    {
        $rosterLink = route('admin.rosters.index');

        $this->actingAs($this->admin())->get('/admin/dashboard')->assertSee($rosterLink, false);
        $this->actingAs($this->doctor())->get('/doctor/dashboard')->assertDontSee($rosterLink, false);
        $this->actingAs($this->patient())->get('/patient/dashboard')->assertDontSee($rosterLink, false);
    }

    #[Group('TC-012'), Group('SRS-REQ-311'), Group('SRS-REQ-312')]
    public function test_tc012_admin_reaches_every_management_page_and_can_create_edit_and_delete(): void
    {
        $doctor = $this->doctor();
        $admin = $this->admin();
        $slot = RosterSlot::factory()->create(['doctor_id' => $doctor->id, 'slot_date' => today()->addDays(2)->toDateString(), 'start_time' => '09:00']);

        foreach (['/admin/rosters', '/admin/rosters/calendar', '/admin/rosters/create', '/admin/rosters/generate', "/admin/rosters/{$slot->id}/edit"] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }

        $this->actingAs($admin)->get('/admin/rosters')->assertSee('Add Slot')->assertSee('Edit')->assertSee('Delete');

        $payload = ['doctor_id' => $doctor->id, 'slot_date' => today()->addDays(3)->toDateString(), 'start_time' => '11:00', 'end_time' => '11:30', 'status' => 'available'];
        $this->actingAs($admin)->post('/admin/rosters', $payload)->assertSessionHasNoErrors();
        $this->actingAs($admin)->put("/admin/rosters/{$slot->id}", array_merge($payload, ['slot_date' => $slot->slot_date->toDateString(), 'start_time' => '09:00', 'end_time' => '09:45']))->assertSessionHasNoErrors();
        $this->actingAs($admin)->delete("/admin/rosters/{$slot->id}")->assertSessionHas('status');

        $this->assertSame(1, RosterSlot::count());
    }

    #[Group('TC-013'), Group('SRS-REQ-311'), Group('SRS-REQ-312')]
    public function test_tc013_a_doctor_sees_only_their_own_schedule_and_no_management_controls(): void
    {
        $doctor = $this->doctor();
        $date = today()->addDays(2)->toDateString();
        $mine = RosterSlot::factory()->create(['doctor_id' => $doctor->id, 'slot_date' => $date]);
        $theirs = RosterSlot::factory()->create(['slot_date' => $date, 'start_time' => '15:00']);

        $this->actingAs($doctor)->get("/doctor/schedule?week={$date}")
            ->assertOk()
            ->assertSee($mine->slot_code)
            ->assertDontSee($theirs->slot_code)
            ->assertDontSee('Add Slot')
            ->assertDontSee('Generate Slots')
            ->assertDontSee('/edit', false)
            ->assertDontSee('confirm-delete', false);
    }

    #[Group('TC-013'), Group('SRS-REQ-311')]
    public function test_tc013_the_doctors_read_only_view_cannot_be_used_to_change_data(): void
    {
        $doctor = $this->doctor();
        $slot = RosterSlot::factory()->create(['doctor_id' => $doctor->id, 'slot_date' => today()->addDays(2)->toDateString(), 'notes' => 'Original']);

        $this->actingAs($doctor)->put("/admin/rosters/{$slot->id}", ['notes' => 'Changed'])->assertForbidden();
        $this->actingAs($doctor)->delete("/admin/rosters/{$slot->id}")->assertForbidden();

        $this->assertSame('Original', $slot->fresh()->notes);
    }
}
