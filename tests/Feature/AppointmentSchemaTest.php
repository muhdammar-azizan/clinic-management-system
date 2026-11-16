<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\RosterSlot;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_appointment_links_patient_slot_service_and_doctor(): void
    {
        $appointment = Appointment::factory()->create();

        $this->assertTrue($appointment->patient->isPatient());
        $this->assertTrue($appointment->doctor->isDoctor());
        $this->assertTrue($appointment->isBooked());
        $this->assertSame($appointment->roster_slot_id, $appointment->active_slot_id);
        $this->assertSame('APT-'.str_pad((string) $appointment->id, 5, '0', STR_PAD_LEFT), $appointment->appointment_code);
    }

    public function test_a_slot_cannot_be_booked_twice(): void
    {
        $first = Appointment::factory()->create();

        $this->expectException(QueryException::class);
        Appointment::factory()->create(['roster_slot_id' => $first->roster_slot_id]);
    }

    public function test_a_cancelled_slot_can_be_booked_again(): void
    {
        $slot = RosterSlot::factory()->create();
        $first = Appointment::factory()->create(['roster_slot_id' => $slot->id]);

        $first->update(['status' => AppointmentStatus::Cancelled]);
        $this->assertNull($first->fresh()->active_slot_id);

        Appointment::factory()->create(['roster_slot_id' => $slot->id]);

        $this->assertSame(2, Appointment::where('roster_slot_id', $slot->id)->count());
    }

    public function test_a_cancelled_appointment_does_not_block_other_cancelled_ones(): void
    {
        $slot = RosterSlot::factory()->create();

        Appointment::factory()->cancelled()->count(2)->create(['roster_slot_id' => $slot->id]);

        $this->assertSame(2, Appointment::count());
    }

    public function test_rescheduling_moves_the_active_slot(): void
    {
        $appointment = Appointment::factory()->create();
        $newSlot = RosterSlot::factory()->create();

        $appointment->update(['roster_slot_id' => $newSlot->id]);

        $this->assertSame($newSlot->id, $appointment->fresh()->active_slot_id);
    }

    public function test_deleting_a_patient_removes_their_appointments(): void
    {
        $appointment = Appointment::factory()->create();
        $appointment->patient->delete();

        $this->assertSame(0, Appointment::count());
    }
}
