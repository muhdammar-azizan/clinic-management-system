<?php

namespace Tests\Feature\Doctor;

use App\Enums\Role;
use App\Models\Appointment;
use App\Models\RosterSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleTest extends TestCase
{
    use RefreshDatabase;

    private function doctor(): User
    {
        return User::factory()->create(['role' => Role::Doctor]);
    }

    public function test_only_doctors_can_open_the_schedule(): void
    {
        $this->get('/doctor/schedule')->assertRedirect('/login');

        foreach ([Role::Patient, Role::Admin] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))->get('/doctor/schedule')->assertForbidden();
        }

        $this->actingAs($this->doctor())->get('/doctor/schedule')->assertOk()->assertSee('No slots');
    }

    public function test_doctor_sees_only_their_own_slots(): void
    {
        $doctor = $this->doctor();
        $mine = RosterSlot::factory()->create(['doctor_id' => $doctor->id, 'slot_date' => '2026-11-18']);
        $theirs = RosterSlot::factory()->create(['slot_date' => '2026-11-18']);

        $this->actingAs($doctor)->get('/doctor/schedule?week=2026-11-18')
            ->assertSee($mine->slot_code)
            ->assertDontSee($theirs->slot_code);
    }

    public function test_doctor_sees_the_requested_week_only(): void
    {
        $doctor = $this->doctor();
        $inside = RosterSlot::factory()->create(['doctor_id' => $doctor->id, 'slot_date' => '2026-11-22']);
        $outside = RosterSlot::factory()->create(['doctor_id' => $doctor->id, 'slot_date' => '2026-11-23']);

        $this->actingAs($doctor)->get('/doctor/schedule?week=2026-11-16')
            ->assertSee($inside->slot_code)
            ->assertDontSee($outside->slot_code);
    }

    public function test_booked_slot_shows_the_patient_and_service(): void
    {
        $doctor = $this->doctor();
        $slot = RosterSlot::factory()->occupied()->create(['doctor_id' => $doctor->id, 'slot_date' => '2026-11-18']);
        $appointment = Appointment::factory()->create(['roster_slot_id' => $slot->id]);

        $this->actingAs($doctor)->get('/doctor/schedule?week=2026-11-18')
            ->assertSee($appointment->patient->name)
            ->assertSee($appointment->service->name);
    }

    public function test_summary_counts_slots(): void
    {
        $doctor = $this->doctor();
        RosterSlot::factory()->create(['doctor_id' => $doctor->id, 'slot_date' => '2026-11-18', 'start_time' => '09:00']);
        RosterSlot::factory()->create(['doctor_id' => $doctor->id, 'slot_date' => '2026-11-18', 'start_time' => '10:00']);
        $booked = RosterSlot::factory()->occupied()->create(['doctor_id' => $doctor->id, 'slot_date' => '2026-11-19']);
        Appointment::factory()->create(['roster_slot_id' => $booked->id]);

        $response = $this->actingAs($doctor)->get('/doctor/schedule?week=2026-11-18');

        $this->assertSame(['slots' => 3, 'available' => 2, 'booked' => 1], $response->viewData('totals'));
    }

    public function test_schedule_is_read_only(): void
    {
        $doctor = $this->doctor();
        $slot = RosterSlot::factory()->create(['doctor_id' => $doctor->id, 'slot_date' => '2026-11-18']);

        $this->actingAs($doctor)->get('/doctor/schedule?week=2026-11-18')
            ->assertDontSee('/edit', false)
            ->assertDontSee('Add Slot');

        $this->actingAs($doctor)->get("/admin/rosters/{$slot->id}/edit")->assertForbidden();
        $this->actingAs($doctor)->delete("/admin/rosters/{$slot->id}")->assertForbidden();
        $this->assertDatabaseHas('roster_slots', ['id' => $slot->id]);
    }

    public function test_menu_shows_my_schedule_for_doctors(): void
    {
        $this->actingAs($this->doctor())->get('/doctor/dashboard')->assertSee(route('doctor.schedule.index'), false);
    }
}
