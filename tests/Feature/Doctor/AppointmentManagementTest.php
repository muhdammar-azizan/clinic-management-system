<?php

namespace Tests\Feature\Doctor;

use App\Enums\AppointmentStatus;
use App\Enums\Role;
use App\Enums\SlotStatus;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\RosterSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->doctor = User::factory()->create(['role' => Role::Doctor]);
    }

    private function appointment(array $slot = [], ?User $doctor = null, string $state = 'booked'): Appointment
    {
        $slot = RosterSlot::factory()->occupied()->create(array_merge([
            'doctor_id' => ($doctor ?? $this->doctor)->id,
            'slot_date' => now()->subDay()->toDateString(), // already started by default
            'start_time' => '10:00',
        ], $slot));

        $factory = Appointment::factory();
        if ($state !== 'booked') {
            $factory = $factory->{$state}();
        }

        return $factory->create(['roster_slot_id' => $slot->id]);
    }

    public function test_only_doctors_can_open_the_pages(): void
    {
        $this->get('/doctor/appointments')->assertRedirect('/login');

        foreach ([Role::Patient, Role::Admin] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))->get('/doctor/appointments')->assertForbidden();
        }
    }

    public function test_doctor_sees_only_appointments_on_their_own_slots(): void
    {
        $mine = $this->appointment();
        $theirs = $this->appointment(['start_time' => '11:00'], User::factory()->create(['role' => Role::Doctor]));

        $this->actingAs($this->doctor)->get('/doctor/appointments')
            ->assertSee($mine->appointment_code)
            ->assertDontSee($theirs->appointment_code);
    }

    public function test_list_is_newest_first_and_can_be_filtered(): void
    {
        $old = $this->appointment(['slot_date' => now()->subDays(5)->toDateString()]);
        $new = $this->appointment(['slot_date' => now()->addDays(5)->toDateString(), 'start_time' => '09:00']);
        $done = $this->appointment(['start_time' => '12:00'], null, 'completed');

        $response = $this->actingAs($this->doctor)->get('/doctor/appointments');
        $this->assertSame([$new->id, $done->id, $old->id], $response->viewData('appointments')->pluck('id')->all());

        $this->actingAs($this->doctor)->get('/doctor/appointments?status=completed')
            ->assertSee($done->appointment_code)
            ->assertDontSee($old->appointment_code);

        $this->actingAs($this->doctor)->get('/doctor/appointments?search='.urlencode($old->patient->email))
            ->assertSee($old->appointment_code)
            ->assertDontSee($new->appointment_code);
    }

    public function test_doctor_can_complete_an_appointment_that_has_started(): void
    {
        $appointment = $this->appointment();

        $this->actingAs($this->doctor)->put("/doctor/appointments/{$appointment->id}", ['status' => 'completed'])
            ->assertRedirect('/doctor/appointments')->assertSessionHas('status');

        $this->assertSame(AppointmentStatus::Completed, $appointment->fresh()->status);
        $this->assertSame(SlotStatus::Occupied, $appointment->slot->fresh()->status);
    }

    public function test_doctor_cannot_complete_a_future_appointment(): void
    {
        $appointment = $this->appointment(['slot_date' => now()->addDays(2)->toDateString()]);

        $this->actingAs($this->doctor)->put("/doctor/appointments/{$appointment->id}", ['status' => 'completed'])
            ->assertSessionHas('error');

        $this->assertSame(AppointmentStatus::Booked, $appointment->fresh()->status);
    }

    public function test_doctor_can_cancel_and_the_slot_is_released(): void
    {
        $appointment = $this->appointment(['slot_date' => now()->addDays(2)->toDateString()]);

        $this->actingAs($this->doctor)->put("/doctor/appointments/{$appointment->id}", ['status' => 'cancelled'])
            ->assertSessionHas('status');

        $this->assertSame(AppointmentStatus::Cancelled, $appointment->fresh()->status);
        $this->assertSame(SlotStatus::Available, $appointment->slot->fresh()->status);
    }

    public function test_finished_appointments_cannot_change_again(): void
    {
        $appointment = $this->appointment([], null, 'completed');

        $this->actingAs($this->doctor)->get("/doctor/appointments/{$appointment->id}/edit")
            ->assertOk()->assertSee('can no longer be changed');

        $this->actingAs($this->doctor)->put("/doctor/appointments/{$appointment->id}", ['status' => 'cancelled'])
            ->assertSessionHas('error');

        $this->assertSame(AppointmentStatus::Completed, $appointment->fresh()->status);
    }

    public function test_status_input_is_validated(): void
    {
        $appointment = $this->appointment();

        $this->actingAs($this->doctor)->put("/doctor/appointments/{$appointment->id}", ['status' => 'booked'])
            ->assertSessionHasErrors('status');
    }

    public function test_doctor_can_delete_and_a_booked_slot_is_released(): void
    {
        $appointment = $this->appointment(['slot_date' => now()->addDays(2)->toDateString()]);

        $this->actingAs($this->doctor)->delete("/doctor/appointments/{$appointment->id}")
            ->assertRedirect('/doctor/appointments')->assertSessionHas('status');

        $this->assertDatabaseMissing('appointments', ['id' => $appointment->id]);
        $this->assertSame(SlotStatus::Available, $appointment->slot->fresh()->status);
    }

    public function test_deleting_a_finished_appointment_keeps_the_slot_status(): void
    {
        $appointment = $this->appointment([], null, 'completed');

        $this->actingAs($this->doctor)->delete("/doctor/appointments/{$appointment->id}");

        $this->assertSame(SlotStatus::Occupied, $appointment->slot->fresh()->status);
    }

    public function test_doctor_cannot_touch_another_doctors_appointment(): void
    {
        $other = User::factory()->create(['role' => Role::Doctor]);
        $appointment = $this->appointment([], $other);

        $this->actingAs($this->doctor)->get("/doctor/appointments/{$appointment->id}/edit")->assertForbidden();
        $this->actingAs($this->doctor)->put("/doctor/appointments/{$appointment->id}", ['status' => 'completed'])->assertForbidden();
        $this->actingAs($this->doctor)->delete("/doctor/appointments/{$appointment->id}")->assertForbidden();

        $this->assertSame(AppointmentStatus::Booked, $appointment->fresh()->status);
    }

    public function test_deleting_keeps_the_medical_record(): void
    {
        $appointment = $this->appointment();
        $record = MedicalRecord::factory()->create([
            'patient_id' => $appointment->patient_id,
            'doctor_id' => $this->doctor->id,
            'appointment_id' => $appointment->id,
        ]);

        $this->actingAs($this->doctor)->delete("/doctor/appointments/{$appointment->id}");

        $this->assertNull($record->fresh()->appointment_id);
    }
}
