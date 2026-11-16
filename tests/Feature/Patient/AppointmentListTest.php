<?php

namespace Tests\Feature\Patient;

use App\Enums\AppointmentStatus;
use App\Enums\Role;
use App\Enums\SlotStatus;
use App\Models\Appointment;
use App\Models\RosterSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentListTest extends TestCase
{
    use RefreshDatabase;

    private function booking(User $patient, array $slot = [], string $state = 'booked'): Appointment
    {
        $slot = RosterSlot::factory()->occupied()->create(array_merge([
            'slot_date' => now()->addDays(2)->toDateString(),
            'start_time' => '10:00',
        ], $slot));

        $factory = Appointment::factory();
        if ($state === 'cancelled') {
            $factory = $factory->cancelled();
        } elseif ($state === 'completed') {
            $factory = $factory->completed();
        }

        return $factory->create(['patient_id' => $patient->id, 'roster_slot_id' => $slot->id]);
    }

    public function test_only_patients_can_open_the_list(): void
    {
        $this->get('/patient/appointments')->assertRedirect('/login');

        foreach ([Role::Doctor, Role::Admin] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))->get('/patient/appointments')->assertForbidden();
        }
    }

    public function test_list_separates_upcoming_from_history_and_hides_other_patients(): void
    {
        $patient = User::factory()->create();
        $upcoming = $this->booking($patient);
        $past = $this->booking($patient, ['slot_date' => now()->subDays(3)->toDateString()]);
        $cancelled = $this->booking($patient, ['start_time' => '11:00'], 'cancelled');
        $someoneElse = $this->booking(User::factory()->create(), ['start_time' => '12:00']);

        $response = $this->actingAs($patient)->get('/patient/appointments')->assertOk();

        $this->assertSame([$upcoming->id], $response->viewData('upcoming')->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$past->id, $cancelled->id], $response->viewData('history')->pluck('id')->all());
        $response->assertDontSee($someoneElse->appointment_code);
    }

    public function test_empty_states(): void
    {
        $this->actingAs(User::factory()->create())->get('/patient/appointments')
            ->assertSee('You have no upcoming appointments.')
            ->assertSee('No past or cancelled appointments yet.');
    }

    public function test_patient_can_cancel_an_upcoming_appointment_and_the_slot_is_released(): void
    {
        $patient = User::factory()->create();
        $appointment = $this->booking($patient);

        $this->actingAs($patient)->patch("/patient/appointments/{$appointment->id}/cancel")
            ->assertRedirect('/patient/appointments')
            ->assertSessionHas('status');

        $this->assertSame(AppointmentStatus::Cancelled, $appointment->fresh()->status);
        $this->assertNull($appointment->fresh()->active_slot_id);
        $this->assertSame(SlotStatus::Available, $appointment->slot->fresh()->status);
    }

    public function test_released_slot_can_be_booked_by_someone_else(): void
    {
        $patient = User::factory()->create();
        $appointment = $this->booking($patient);
        $this->actingAs($patient)->patch("/patient/appointments/{$appointment->id}/cancel");

        $this->assertTrue(RosterSlot::bookable()->whereKey($appointment->roster_slot_id)->exists());
    }

    public function test_patient_cannot_cancel_someone_elses_appointment(): void
    {
        $owner = User::factory()->create();
        $appointment = $this->booking($owner);

        $this->actingAs(User::factory()->create())->patch("/patient/appointments/{$appointment->id}/cancel")->assertForbidden();

        $this->assertSame(AppointmentStatus::Booked, $appointment->fresh()->status);
    }

    public function test_completed_cancelled_and_past_appointments_cannot_be_cancelled(): void
    {
        $patient = User::factory()->create();
        $cases = [
            $this->booking($patient, ['start_time' => '09:00'], 'completed'),
            $this->booking($patient, ['start_time' => '09:30'], 'cancelled'),
            $this->booking($patient, ['slot_date' => now()->subDay()->toDateString(), 'start_time' => '10:30']),
        ];

        foreach ($cases as $appointment) {
            $before = $appointment->fresh()->status;

            $this->actingAs($patient)->patch("/patient/appointments/{$appointment->id}/cancel")
                ->assertSessionHas('error');

            $this->assertSame($before, $appointment->fresh()->status);
        }
    }

    public function test_only_upcoming_rows_have_a_cancel_button(): void
    {
        $patient = User::factory()->create();
        $upcoming = $this->booking($patient);
        $past = $this->booking($patient, ['slot_date' => now()->subDays(2)->toDateString(), 'start_time' => '09:00']);

        $this->actingAs($patient)->get('/patient/appointments')
            ->assertSee(route('patient.appointments.cancel', $upcoming), false)
            ->assertDontSee(route('patient.appointments.cancel', $past), false);
    }
}
