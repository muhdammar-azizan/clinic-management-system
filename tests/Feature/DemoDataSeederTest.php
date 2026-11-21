<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\Role;
use App\Enums\SlotStatus;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\RosterSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_demo_data_has_several_doctors_and_patients_who_can_log_in(): void
    {
        $this->assertSame(5, User::where('role', Role::Doctor)->count());
        $this->assertSame(7, User::where('role', Role::Patient)->count());
        $this->assertSame(1, User::where('role', Role::Admin)->count());

        $this->post('/login', ['email' => 'aisha.rahman@clinic.test', 'password' => 'password'])
            ->assertRedirect('/dashboard');
    }

    public function test_every_doctor_has_future_slots_on_weekdays_only(): void
    {
        foreach (User::where('role', Role::Doctor)->get() as $doctor) {
            $this->assertGreaterThan(0, RosterSlot::where('doctor_id', $doctor->id)->whereDate('slot_date', '>', today())->count());
        }

        $weekendFutureSlots = RosterSlot::whereDate('slot_date', '>', today())->get()->filter(fn ($slot) => $slot->slot_date->isWeekend());
        $this->assertCount(0, $weekendFutureSlots);
    }

    public function test_slot_status_matches_the_appointment_on_it(): void
    {
        foreach (Appointment::with('slot')->get() as $appointment) {
            $expected = $appointment->status === AppointmentStatus::Cancelled ? SlotStatus::Available : SlotStatus::Occupied;

            $this->assertSame($expected, $appointment->slot->status, "Appointment {$appointment->id} and its slot disagree");
        }

        $this->assertSame(
            Appointment::where('status', '!=', AppointmentStatus::Cancelled)->count(),
            RosterSlot::where('status', SlotStatus::Occupied)->count(),
            'An occupied slot has no appointment'
        );
    }

    public function test_every_finished_appointment_has_one_medical_record_from_its_own_doctor(): void
    {
        $completed = Appointment::with('slot')->where('status', AppointmentStatus::Completed)->get();

        $this->assertGreaterThanOrEqual(5, $completed->count());

        foreach ($completed as $appointment) {
            $record = MedicalRecord::where('appointment_id', $appointment->id)->first();

            $this->assertNotNull($record);
            $this->assertSame($appointment->patient_id, $record->patient_id);
            $this->assertSame($appointment->slot->doctor_id, $record->doctor_id);
        }
    }

    public function test_demo_patient_has_an_upcoming_appointment_and_a_record(): void
    {
        $patient = User::where('email', 'patient@clinic.test')->firstOrFail();

        $upcoming = Appointment::where('patient_id', $patient->id)
            ->where('status', AppointmentStatus::Booked)
            ->whereHas('slot', fn ($query) => $query->whereDate('slot_date', '>', today()))
            ->count();

        $this->assertGreaterThan(0, $upcoming);
        $this->assertGreaterThan(0, MedicalRecord::where('patient_id', $patient->id)->count());
    }
}
