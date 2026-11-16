<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\Appointment;
use App\Models\RosterSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RosterCalendarTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => Role::Admin]);
    }

    public function test_only_admin_can_open_the_calendar(): void
    {
        $this->get('/admin/rosters/calendar')->assertRedirect('/login');
        $this->actingAs(User::factory()->create(['role' => Role::Doctor]))->get('/admin/rosters/calendar')->assertForbidden();
        $this->actingAs($this->admin())->get('/admin/rosters/calendar')->assertOk()->assertSee('No slots');
    }

    public function test_calendar_shows_slots_of_the_requested_week_only(): void
    {
        $inside = RosterSlot::factory()->create(['slot_date' => '2026-11-18', 'start_time' => '09:30']);
        $outside = RosterSlot::factory()->create(['slot_date' => '2026-11-25', 'start_time' => '14:00']);

        $this->actingAs($this->admin())->get('/admin/rosters/calendar?week=2026-11-20')
            ->assertOk()
            ->assertSee('16 Nov')
            ->assertSee('22 Nov 2026')
            ->assertSee($inside->slot_code)
            ->assertSee('09:30')
            ->assertDontSee($outside->slot_code);
    }

    public function test_week_starts_on_monday_and_includes_sunday(): void
    {
        $sunday = RosterSlot::factory()->create(['slot_date' => '2026-11-22', 'start_time' => '10:00']);
        $nextMonday = RosterSlot::factory()->create(['slot_date' => '2026-11-23', 'start_time' => '10:30']);

        $this->actingAs($this->admin())->get('/admin/rosters/calendar?week=2026-11-18')
            ->assertSee($sunday->slot_code)
            ->assertDontSee($nextMonday->slot_code);
    }

    public function test_calendar_can_filter_by_doctor(): void
    {
        $mine = RosterSlot::factory()->create(['slot_date' => '2026-11-18']);
        $other = RosterSlot::factory()->create(['slot_date' => '2026-11-18', 'start_time' => '15:00']);

        $this->actingAs($this->admin())->get("/admin/rosters/calendar?week=2026-11-18&doctor={$mine->doctor_id}")
            ->assertSee('SLT-'.str_pad((string) $mine->id, 5, '0', STR_PAD_LEFT))
            ->assertDontSee('SLT-'.str_pad((string) $other->id, 5, '0', STR_PAD_LEFT));
    }

    public function test_invalid_week_falls_back_to_the_current_week(): void
    {
        $this->actingAs($this->admin())->get('/admin/rosters/calendar?week=not-a-date')
            ->assertOk()
            ->assertSee(today()->startOfWeek()->format('d M'));
    }

    public function test_booked_slots_have_no_edit_link(): void
    {
        $free = RosterSlot::factory()->create(['slot_date' => '2026-11-18', 'start_time' => '09:00']);
        $booked = RosterSlot::factory()->occupied()->create(['slot_date' => '2026-11-18', 'start_time' => '10:00']);
        Appointment::factory()->create(['roster_slot_id' => $booked->id]);

        $this->actingAs($this->admin())->get('/admin/rosters/calendar?week=2026-11-18')
            ->assertSee(route('admin.rosters.edit', $free), false)
            ->assertDontSee(route('admin.rosters.edit', $booked), false)
            ->assertSee('(booked)');
    }

    public function test_list_and_calendar_link_to_each_other(): void
    {
        $this->actingAs($this->admin())->get('/admin/rosters')->assertSee(route('admin.rosters.calendar'), false);
        $this->actingAs($this->admin())->get('/admin/rosters/calendar')->assertSee(route('admin.rosters.index'), false);
    }
}
