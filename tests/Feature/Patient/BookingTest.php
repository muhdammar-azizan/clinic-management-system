<?php

namespace Tests\Feature\Patient;

use App\Enums\AppointmentStatus;
use App\Enums\Role;
use App\Enums\SlotStatus;
use App\Models\Appointment;
use App\Models\DoctorProfile;
use App\Models\RosterSlot;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    private function patient(): User
    {
        return User::factory()->create();
    }

    /**
     * A doctor of the given service with one open slot a few days from now.
     */
    private function openSlot(?Service $service = null, array $overrides = []): RosterSlot
    {
        $profile = DoctorProfile::factory()->create($service ? ['service_id' => $service->id] : []);

        return RosterSlot::factory()->create(array_merge([
            'doctor_id' => $profile->user_id,
            'slot_date' => now()->addDays(3)->toDateString(),
            'start_time' => '10:00',
        ], $overrides));
    }

    public function test_only_patients_can_book(): void
    {
        $this->get('/patient/book')->assertRedirect('/login');

        foreach ([Role::Doctor, Role::Admin] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))->get('/patient/book')->assertForbidden();
            $this->actingAs(User::factory()->create(['role' => $role]))->post('/patient/book', [])->assertForbidden();
        }
    }

    public function test_page_lists_active_services_and_no_slots_until_one_is_chosen(): void
    {
        $service = Service::factory()->create(['name' => 'Dermatology']);
        Service::factory()->create(['name' => 'Retired Service', 'is_active' => false]);
        $slot = $this->openSlot($service);

        $this->actingAs($this->patient())->get('/patient/book')
            ->assertOk()
            ->assertSee('Dermatology')
            ->assertDontSee('Retired Service')
            ->assertDontSee('Confirm Booking');

        $this->actingAs($this->patient())->get('/patient/book?service='.$service->id)
            ->assertSee($slot->doctor->name)
            ->assertSee('10:00')
            ->assertSee('Confirm Booking');
    }

    public function test_only_bookable_slots_of_the_chosen_service_are_listed(): void
    {
        $service = Service::factory()->create();
        $good = $this->openSlot($service, ['start_time' => '09:00']);
        $occupied = $this->openSlot($service, ['start_time' => '09:30', 'status' => SlotStatus::Occupied]);
        $past = $this->openSlot($service, ['slot_date' => now()->subDay()->toDateString(), 'start_time' => '09:45']);
        $tooFar = $this->openSlot($service, ['slot_date' => now()->addDays(45)->toDateString(), 'start_time' => '10:15']);
        $otherService = $this->openSlot(null, ['start_time' => '10:30']);

        $response = $this->actingAs($this->patient())->get('/patient/book?service='.$service->id);

        $listed = $response->viewData('slotsByDay')->flatten()->pluck('id')->all();
        $this->assertSame([$good->id], $listed);
    }

    public function test_slot_earlier_today_is_not_bookable(): void
    {
        $this->travelTo(now()->setTime(12, 0));

        $service = Service::factory()->create();
        $morning = $this->openSlot($service, ['slot_date' => today()->toDateString(), 'start_time' => '09:00']);
        $afternoon = $this->openSlot($service, ['slot_date' => today()->toDateString(), 'start_time' => '15:00']);

        $listed = $this->actingAs($this->patient())->get('/patient/book?service='.$service->id)
            ->viewData('slotsByDay')->flatten()->pluck('id')->all();

        $this->assertSame([$afternoon->id], $listed);
    }

    public function test_patient_can_book_a_slot(): void
    {
        $service = Service::factory()->create();
        $slot = $this->openSlot($service);
        $patient = $this->patient();

        $this->actingAs($patient)->post('/patient/book', [
            'service_id' => $service->id,
            'roster_slot_id' => $slot->id,
            'reason' => 'Persistent cough',
        ])->assertRedirect('/patient/appointments')->assertSessionHas('status');

        $appointment = Appointment::first();
        $this->assertSame($patient->id, $appointment->patient_id);
        $this->assertSame($service->id, $appointment->service_id);
        $this->assertSame(AppointmentStatus::Booked, $appointment->status);
        $this->assertSame('Persistent cough', $appointment->reason);
        $this->assertSame(SlotStatus::Occupied, $slot->fresh()->status);
    }

    public function test_a_slot_cannot_be_booked_twice(): void
    {
        $service = Service::factory()->create();
        $slot = $this->openSlot($service);
        $payload = ['service_id' => $service->id, 'roster_slot_id' => $slot->id];

        $this->actingAs($this->patient())->post('/patient/book', $payload)->assertSessionHas('status');
        $this->actingAs($this->patient())->post('/patient/book', $payload)->assertSessionHas('error');

        $this->assertSame(1, Appointment::count());
    }

    public function test_slot_must_belong_to_the_chosen_service(): void
    {
        $service = Service::factory()->create();
        $otherSlot = $this->openSlot();

        $this->actingAs($this->patient())->post('/patient/book', [
            'service_id' => $service->id,
            'roster_slot_id' => $otherSlot->id,
        ])->assertSessionHas('error');

        $this->assertSame(0, Appointment::count());
        $this->assertSame(SlotStatus::Available, $otherSlot->fresh()->status);
    }

    public function test_past_or_occupied_slots_are_rejected(): void
    {
        $service = Service::factory()->create();
        $past = $this->openSlot($service, ['slot_date' => now()->subDay()->toDateString()]);
        $taken = $this->openSlot($service, ['start_time' => '11:00', 'status' => SlotStatus::Occupied]);
        $patient = $this->patient();

        foreach ([$past, $taken] as $slot) {
            $this->actingAs($patient)->post('/patient/book', [
                'service_id' => $service->id,
                'roster_slot_id' => $slot->id,
            ])->assertSessionHas('error');
        }

        $this->assertSame(0, Appointment::count());
    }

    public function test_patient_cannot_hold_two_appointments_at_the_same_time(): void
    {
        $service = Service::factory()->create();
        $first = $this->openSlot($service);
        $second = $this->openSlot($service, ['slot_date' => $first->slot_date->toDateString(), 'start_time' => '10:00']);
        $patient = $this->patient();

        $this->actingAs($patient)->post('/patient/book', ['service_id' => $service->id, 'roster_slot_id' => $first->id]);
        $this->actingAs($patient)->post('/patient/book', ['service_id' => $service->id, 'roster_slot_id' => $second->id])
            ->assertSessionHas('error');

        $this->assertSame(1, Appointment::count());
    }

    public function test_cancelled_booking_frees_the_slot_for_booking_again(): void
    {
        $service = Service::factory()->create();
        $slot = $this->openSlot($service, ['status' => SlotStatus::Available]);
        Appointment::factory()->cancelled()->create(['roster_slot_id' => $slot->id]);

        $this->actingAs($this->patient())->post('/patient/book', [
            'service_id' => $service->id,
            'roster_slot_id' => $slot->id,
        ])->assertSessionHas('status');
    }

    public function test_validation_errors(): void
    {
        $this->actingAs($this->patient())->post('/patient/book', [])
            ->assertSessionHasErrors(['service_id', 'roster_slot_id']);

        $inactive = Service::factory()->create(['is_active' => false]);
        $this->actingAs($this->patient())->post('/patient/book', ['service_id' => $inactive->id, 'roster_slot_id' => 1])
            ->assertSessionHasErrors('service_id');
    }

    public function test_menu_shows_book_appointment_for_patients(): void
    {
        $this->actingAs($this->patient())->get('/patient/dashboard')->assertSee(route('patient.booking.create'), false);
    }
}
