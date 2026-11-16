<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\Appointment;
use App\Models\RosterSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RosterSlotManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => Role::Admin]);
    }

    private function doctor(): User
    {
        return User::factory()->create(['role' => Role::Doctor]);
    }

    private function payload(User $doctor, array $overrides = []): array
    {
        return array_merge([
            'doctor_id' => $doctor->id,
            'slot_date' => now()->addDays(2)->toDateString(),
            'start_time' => '09:30',
            'status' => 'available',
            'notes' => 'Morning clinic',
        ], $overrides);
    }

    public function test_only_admin_can_open_the_roster_pages(): void
    {
        $this->get('/admin/rosters')->assertRedirect('/login');

        foreach ([Role::Patient, Role::Doctor] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))->get('/admin/rosters')->assertForbidden();
        }

        $this->actingAs($this->admin())->get('/admin/rosters')->assertOk();
    }

    public function test_index_shows_slots_with_code_doctor_and_status(): void
    {
        $slot = RosterSlot::factory()->create();

        $this->actingAs($this->admin())->get('/admin/rosters')
            ->assertSee($slot->slot_code)
            ->assertSee($slot->doctor->name)
            ->assertSee('Available');
    }

    public function test_admin_can_create_a_slot(): void
    {
        $doctor = $this->doctor();

        $this->actingAs($this->admin())->post('/admin/rosters', $this->payload($doctor))
            ->assertRedirect('/admin/rosters')->assertSessionHas('status');

        $slot = RosterSlot::first();
        $this->assertSame($doctor->id, $slot->doctor_id);
        $this->assertSame('09:30', $slot->start_time_label);
    }

    public function test_slot_validation(): void
    {
        $doctor = $this->doctor();

        $this->actingAs($this->admin())->post('/admin/rosters', $this->payload($doctor, [
            'slot_date' => now()->subDay()->toDateString(),
            'start_time' => 'late',
            'status' => 'maybe',
        ]))->assertSessionHasErrors(['slot_date', 'start_time', 'status']);

        $patient = User::factory()->create();
        $this->actingAs($this->admin())->post('/admin/rosters', $this->payload($patient))
            ->assertSessionHasErrors('doctor_id');
    }

    public function test_a_doctor_cannot_get_two_slots_at_the_same_time(): void
    {
        $doctor = $this->doctor();
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/rosters', $this->payload($doctor))->assertSessionHasNoErrors();
        $this->actingAs($admin)->post('/admin/rosters', $this->payload($doctor))->assertSessionHasErrors('start_time');

        $this->assertSame(1, RosterSlot::count());
    }

    public function test_admin_can_update_a_slot_without_clashing_with_itself(): void
    {
        $slot = RosterSlot::factory()->create(['start_time' => '10:00']);

        $this->actingAs($this->admin())->put("/admin/rosters/{$slot->id}", $this->payload($slot->doctor, [
            'slot_date' => $slot->slot_date->toDateString(),
            'start_time' => '10:00',
            'status' => 'occupied',
            'notes' => 'Doctor on call',
        ]))->assertSessionHasNoErrors()->assertRedirect('/admin/rosters');

        $this->assertSame('occupied', $slot->fresh()->status->value);
        $this->assertSame('Doctor on call', $slot->fresh()->notes);
    }

    public function test_admin_can_delete_a_free_slot(): void
    {
        $slot = RosterSlot::factory()->create();

        $this->actingAs($this->admin())->delete("/admin/rosters/{$slot->id}")->assertSessionHas('status');

        $this->assertDatabaseMissing('roster_slots', ['id' => $slot->id]);
    }

    public function test_slot_with_a_booked_appointment_cannot_be_edited_or_deleted(): void
    {
        $slot = RosterSlot::factory()->occupied()->create();
        Appointment::factory()->create(['roster_slot_id' => $slot->id]);
        $admin = $this->admin();

        $this->actingAs($admin)->get("/admin/rosters/{$slot->id}/edit")->assertRedirect('/admin/rosters');
        $this->actingAs($admin)->put("/admin/rosters/{$slot->id}", $this->payload($slot->doctor))->assertSessionHas('error');
        $this->actingAs($admin)->delete("/admin/rosters/{$slot->id}")->assertSessionHas('error');

        $this->assertDatabaseHas('roster_slots', ['id' => $slot->id]);
    }

    public function test_cancelled_appointment_no_longer_locks_the_slot(): void
    {
        $slot = RosterSlot::factory()->create();
        Appointment::factory()->cancelled()->create(['roster_slot_id' => $slot->id]);

        $this->actingAs($this->admin())->get("/admin/rosters/{$slot->id}/edit")->assertOk();
    }
}
