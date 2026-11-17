<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\RosterSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    private function booking(?User $doctor = null, ?User $patient = null, array $slot = [], string $state = 'booked'): Appointment
    {
        $slot = RosterSlot::factory()->occupied()->create(array_merge([
            'doctor_id' => ($doctor ?? User::factory()->create(['role' => Role::Doctor]))->id,
            'slot_date' => now()->addDays(2)->toDateString(),
            'start_time' => '10:00',
        ], $slot));

        $factory = Appointment::factory();
        if ($state !== 'booked') {
            $factory = $factory->{$state}();
        }

        return $factory->create(array_filter(['roster_slot_id' => $slot->id, 'patient_id' => $patient?->id]));
    }

    public function test_admin_dashboard_counts(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        User::factory()->count(3)->create();                                  // patients
        $doctor = User::factory()->create(['role' => Role::Doctor]);
        $this->booking($doctor, null, ['start_time' => '09:00']);              // upcoming
        $this->booking($doctor, null, ['start_time' => '09:30']);              // upcoming
        $this->booking($doctor, null, ['start_time' => '10:30'], 'cancelled'); // not counted as upcoming
        $this->booking($doctor, null, ['slot_date' => today()->toDateString(), 'start_time' => '00:01'], 'completed'); // today
        RosterSlot::factory()->create(['doctor_id' => $doctor->id, 'start_time' => '16:00']);                          // open slot
        MedicalRecord::factory()->count(2)->create();

        $stats = $this->actingAs($admin)->get('/admin/dashboard')->assertOk()->viewData('stats');

        // 3 patients here plus the patients created by the appointment and record factories.
        $this->assertSame(User::where('role', Role::Patient)->count(), $stats['patients']);
        $this->assertGreaterThanOrEqual(3, $stats['patients']);
        $this->assertSame(User::where('role', Role::Doctor)->count(), $stats['doctors']);
        $this->assertSame(2, $stats['upcoming']);
        $this->assertSame(1, $stats['today']);
        $this->assertSame(1, $stats['openSlots']);
        $this->assertSame(2, $stats['records']);
    }

    public function test_admin_dashboard_lists_next_appointments_in_time_order(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $later = $this->booking(null, null, ['slot_date' => now()->addDays(5)->toDateString()]);
        $sooner = $this->booking(null, null, ['slot_date' => now()->addDays(1)->toDateString()]);
        $past = $this->booking(null, null, ['slot_date' => now()->subDay()->toDateString()]);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $this->assertSame([$sooner->id, $later->id], $response->viewData('nextAppointments')->pluck('id')->all());
        $response->assertDontSee($past->appointment_code);
    }

    public function test_admin_dashboard_shows_no_clinical_content(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        MedicalRecord::factory()->create(['diagnosis' => 'Secret diagnosis text']);

        $this->actingAs($admin)->get('/admin/dashboard')->assertDontSee('Secret diagnosis text');
    }

    public function test_doctor_dashboard_counts_only_their_own_data(): void
    {
        $doctor = User::factory()->create(['role' => Role::Doctor]);
        $other = User::factory()->create(['role' => Role::Doctor]);

        $this->booking($doctor, null, ['start_time' => '09:00']);                                  // upcoming
        $this->booking($other, null, ['start_time' => '09:00']);                                   // someone else's
        $todayDone = $this->booking($doctor, null, ['slot_date' => today()->toDateString(), 'start_time' => '00:01'], 'completed');
        MedicalRecord::factory()->create(['doctor_id' => $doctor->id]);
        MedicalRecord::factory()->create(['doctor_id' => $other->id]);

        $response = $this->actingAs($doctor)->get('/doctor/dashboard')->assertOk();
        $stats = $response->viewData('stats');

        $this->assertSame(1, $stats['upcoming']);
        $this->assertSame(1, $stats['today']);
        $this->assertSame(1, $stats['awaiting']);
        $this->assertSame(1, $stats['records']);
        $this->assertSame([$todayDone->id], $response->viewData('todays')->pluck('id')->all());
    }

    public function test_doctor_dashboard_lists_visits_awaiting_a_record_and_drops_them_once_written(): void
    {
        $doctor = User::factory()->create(['role' => Role::Doctor]);
        $started = $this->booking($doctor, null, ['slot_date' => now()->subDay()->toDateString()]);          // started, still booked
        $future = $this->booking($doctor, null, ['start_time' => '11:00']);                                  // not yet
        $cancelled = $this->booking($doctor, null, ['slot_date' => now()->subDays(2)->toDateString()], 'cancelled');

        $ids = fn () => $this->actingAs($doctor)->get('/doctor/dashboard')->viewData('awaitingRecord')->pluck('id')->all();

        $this->assertSame([$started->id], $ids());

        MedicalRecord::factory()->create(['doctor_id' => $doctor->id, 'patient_id' => $started->patient_id, 'appointment_id' => $started->id]);

        $this->assertSame([], $ids());
        $this->assertNotContains($future->id, $ids());
        $this->assertNotContains($cancelled->id, $ids());
    }

    public function test_doctor_awaiting_list_matches_the_record_rule(): void
    {
        $doctor = User::factory()->create(['role' => Role::Doctor]);
        $appointments = [
            $this->booking($doctor, null, ['slot_date' => now()->subDay()->toDateString(), 'start_time' => '09:00']),
            $this->booking($doctor, null, ['slot_date' => now()->addDay()->toDateString(), 'start_time' => '09:30']),
            $this->booking($doctor, null, ['slot_date' => now()->subDay()->toDateString(), 'start_time' => '10:00'], 'completed'),
            $this->booking($doctor, null, ['slot_date' => now()->subDay()->toDateString(), 'start_time' => '10:30'], 'cancelled'),
        ];

        $listed = Appointment::awaitingRecord()->pluck('id')->all();

        foreach ($appointments as $appointment) {
            $this->assertSame($appointment->canHaveMedicalRecord(), in_array($appointment->id, $listed), "Mismatch for appointment {$appointment->id}");
        }
    }

    public function test_patient_dashboard_shows_next_appointment_and_counts(): void
    {
        $patient = User::factory()->create();
        $soon = $this->booking(null, $patient, ['slot_date' => now()->addDay()->toDateString()]);
        $this->booking(null, $patient, ['slot_date' => now()->addDays(6)->toDateString()]);
        $this->booking(null, $patient, ['start_time' => '12:00'], 'cancelled');
        $this->booking(User::factory()->create(['role' => Role::Doctor]));      // another patient's
        MedicalRecord::factory()->create(['patient_id' => $patient->id]);

        $response = $this->actingAs($patient)->get('/patient/dashboard')->assertOk();

        $this->assertTrue($response->viewData('next')->is($soon));
        $this->assertSame(2, $response->viewData('upcomingCount'));
        $this->assertSame(1, $response->viewData('recordsCount'));
        $response->assertSee($soon->doctor->name)->assertSee('Latest medical record');
    }

    public function test_new_patient_sees_friendly_empty_states(): void
    {
        $this->actingAs(User::factory()->create())->get('/patient/dashboard')
            ->assertOk()
            ->assertSee('Nothing booked yet')
            ->assertSee('You have no upcoming appointments.')
            ->assertDontSee('Latest medical record');
    }

    public function test_empty_dashboards_render_for_doctor_and_admin(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Doctor]))->get('/doctor/dashboard')
            ->assertOk()->assertSee('No appointments today.')->assertSee('You are all caught up');

        $this->actingAs(User::factory()->create(['role' => Role::Admin]))->get('/admin/dashboard')
            ->assertOk()->assertSee('No upcoming appointments.');
    }

    public function test_patient_dashboard_still_shows_the_flash_message(): void
    {
        $this->actingAs(User::factory()->create())->withSession(['status' => 'Saved for you.'])
            ->get('/patient/dashboard')->assertSee('Saved for you.');
    }
}
