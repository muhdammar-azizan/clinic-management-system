<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\Appointment;
use App\Models\RosterSlot;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentOverviewTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => Role::Admin]);
    }

    private function appointment(array $slot = [], array $attributes = []): Appointment
    {
        $slot = RosterSlot::factory()->occupied()->create($slot);

        return Appointment::factory()->create(array_merge(['roster_slot_id' => $slot->id], $attributes));
    }

    private function search(string $query)
    {
        return $this->actingAs($this->admin)->get('/admin/appointments?'.$query);
    }

    public function test_only_admin_can_open_the_overview(): void
    {
        $this->get('/admin/appointments')->assertRedirect('/login');

        foreach ([Role::Patient, Role::Doctor] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))->get('/admin/appointments')->assertForbidden();
        }

        $this->search('')->assertOk();
    }

    public function test_admin_sees_all_appointments_with_patient_doctor_and_service(): void
    {
        $a = $this->appointment();
        $b = $this->appointment();

        $this->search('')
            ->assertSee($a->appointment_code)
            ->assertSee($b->appointment_code)
            ->assertSee($a->patient->name)
            ->assertSee($a->service->name);
    }

    public function test_it_is_view_only(): void
    {
        $a = $this->appointment();

        $this->search('')
            ->assertDontSee('confirm-delete', false)
            ->assertDontSee('/edit', false)
            ->assertDontSee('<th class="text-right">Actions</th>', false);

        $this->actingAs($this->admin)->delete("/doctor/appointments/{$a->id}")->assertForbidden();
        $this->actingAs($this->admin)->patch("/patient/appointments/{$a->id}/cancel")->assertForbidden();
    }

    public function test_filter_by_doctor_service_and_status(): void
    {
        $target = $this->appointment();
        $other = $this->appointment(['start_time' => '15:00'], ['status' => 'cancelled']);

        $this->search('doctor='.$target->slot->doctor_id)->assertSee($target->appointment_code)->assertDontSee($other->appointment_code);
        $this->search('service='.$target->service_id)->assertSee($target->appointment_code)->assertDontSee($other->appointment_code);
        $this->search('status=cancelled')->assertSee($other->appointment_code)->assertDontSee($target->appointment_code);
    }

    public function test_filter_by_date_range(): void
    {
        $early = $this->appointment(['slot_date' => '2026-12-01']);
        $inside = $this->appointment(['slot_date' => '2026-12-10']);
        $late = $this->appointment(['slot_date' => '2026-12-31']);

        $this->search('from=2026-12-05&to=2026-12-20')
            ->assertSee($inside->appointment_code)
            ->assertDontSee($early->appointment_code)
            ->assertDontSee($late->appointment_code);
    }

    public function test_search_by_appointment_id_or_patient(): void
    {
        $target = $this->appointment();
        $other = $this->appointment();

        foreach ([$target->appointment_code, strtolower($target->appointment_code), (string) $target->id] as $term) {
            $this->search('search='.urlencode($term))->assertSee($target->appointment_code)->assertDontSee($other->appointment_code);
        }

        $this->search('search='.urlencode($other->patient->email))->assertSee($other->appointment_code)->assertDontSee($target->appointment_code);
    }

    public function test_invalid_filters_are_rejected_and_empty_results_explained(): void
    {
        $this->search('status=maybe')->assertSessionHasErrors('status');
        $this->search('from=2026-12-20&to=2026-12-01')->assertSessionHasErrors('to');
        $this->search('search=zzzz-nothing')->assertSee('No appointments match your filters.');
    }
}
