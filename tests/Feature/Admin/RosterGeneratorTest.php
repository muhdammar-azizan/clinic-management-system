<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\RosterSlot;
use App\Models\User;
use App\Services\RosterGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RosterGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        // A fixed Monday at 08:00, so weekdays and "today" are predictable.
        $this->travelTo(now()->next('Monday')->setTime(8, 0));

        $this->admin = User::factory()->create(['role' => Role::Admin]);
        $this->doctor = User::factory()->create(['role' => Role::Doctor]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'doctor_id' => $this->doctor->id,
            'from' => today()->toDateString(),
            'to' => today()->addDays(6)->toDateString(),   // Monday to Sunday
            'days' => [1, 2, 3, 4, 5],
            'work_start' => '09:00',
            'work_end' => '12:00',
            'duration' => 30,
            'break_start' => '',
            'break_end' => '',
        ], $overrides);
    }

    public function test_only_admin_can_generate(): void
    {
        $this->get('/admin/rosters/generate')->assertRedirect('/login');

        foreach ([Role::Doctor, Role::Patient] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->actingAs($user)->get('/admin/rosters/generate')->assertForbidden();
            $this->actingAs($user)->post('/admin/rosters/generate', $this->payload())->assertForbidden();
        }

        $this->actingAs($this->admin)->get('/admin/rosters/generate')->assertOk()->assertSee('Generate Slots');
        $this->assertSame(0, RosterSlot::count());
    }

    public function test_it_creates_slots_for_the_chosen_weekdays_only(): void
    {
        $this->actingAs($this->admin)->post('/admin/rosters/generate', $this->payload())
            ->assertRedirect('/admin/rosters')
            ->assertSessionHas('status');

        // Five weekdays, six 30 minute sessions each between 09:00 and 12:00.
        $this->assertSame(30, RosterSlot::count());
        $this->assertSame(0, RosterSlot::whereIn('slot_date', [today()->addDays(5)->toDateString(), today()->addDays(6)->toDateString()])->count());
    }

    public function test_slots_have_the_right_start_and_end_times_and_notes(): void
    {
        $this->actingAs($this->admin)->post('/admin/rosters/generate', $this->payload([
            'to' => today()->toDateString(),
            'notes' => 'Room 4A',
        ]));

        $slots = RosterSlot::orderBy('start_time')->get();

        $this->assertSame(['09:00 - 09:30', '09:30 - 10:00', '10:00 - 10:30', '10:30 - 11:00', '11:00 - 11:30', '11:30 - 12:00'], $slots->pluck('time_range')->all());
        $this->assertSame(['Room 4A'], $slots->pluck('notes')->unique()->values()->all());
        $this->assertTrue($slots->every(fn ($slot) => $slot->doctor_id === $this->doctor->id && $slot->isAvailable()));
    }

    public function test_a_break_leaves_out_the_sessions_that_touch_it(): void
    {
        $this->actingAs($this->admin)->post('/admin/rosters/generate', $this->payload([
            'to' => today()->toDateString(),
            'work_end' => '15:00',
            'duration' => 60,
            'break_start' => '12:00',
            'break_end' => '13:00',
        ]));

        $this->assertSame(['09:00', '10:00', '11:00', '13:00', '14:00'], RosterSlot::orderBy('start_time')->pluck('start_time')->map(fn ($t) => substr($t, 0, 5))->all());
    }

    public function test_a_session_that_does_not_fit_before_closing_is_not_created(): void
    {
        $this->actingAs($this->admin)->post('/admin/rosters/generate', $this->payload([
            'to' => today()->toDateString(),
            'work_end' => '10:20',
            'duration' => 45,
        ]));

        // 09:00-09:45 fits, 09:45-10:30 would run past 10:20.
        $this->assertSame(['09:00 - 09:45'], RosterSlot::get()->pluck('time_range')->all());
    }

    public function test_sessions_that_overlap_existing_slots_are_skipped_and_reported(): void
    {
        $existing = RosterSlot::factory()->create([
            'doctor_id' => $this->doctor->id,
            'slot_date' => today()->toDateString(),
            'start_time' => '09:15',
        ]);
        $existing->forceFill(['end_time' => '09:45'])->save();

        $response = $this->actingAs($this->admin)->post('/admin/rosters/generate', $this->payload(['to' => today()->toDateString()]));

        // 09:00-09:30 and 09:30-10:00 both touch 09:15-09:45.
        $this->assertSame(5, RosterSlot::count());
        $this->assertStringContainsString('2 overlapping an existing slot', session('status'));
        $this->assertSame($existing->id, RosterSlot::where('start_time', '09:15')->value('id'));
    }

    public function test_running_the_same_pattern_twice_creates_nothing_new(): void
    {
        $this->actingAs($this->admin)->post('/admin/rosters/generate', $this->payload());
        $this->assertSame(30, RosterSlot::count());

        $this->actingAs($this->admin)->post('/admin/rosters/generate', $this->payload())
            ->assertSessionHas('error');

        $this->assertSame(30, RosterSlot::count());
    }

    public function test_sessions_already_past_today_are_skipped(): void
    {
        $this->travelTo(now()->setTime(10, 15));

        $this->actingAs($this->admin)->post('/admin/rosters/generate', $this->payload(['to' => today()->toDateString()]));

        $this->assertSame(['10:30', '11:00', '11:30'], RosterSlot::orderBy('start_time')->pluck('start_time')->map(fn ($t) => substr($t, 0, 5))->all());
        $this->assertStringContainsString('3 already in the past', session('status'));
    }

    public function test_it_never_touches_other_doctors(): void
    {
        $other = User::factory()->create(['role' => Role::Doctor]);
        RosterSlot::factory()->create(['doctor_id' => $other->id, 'slot_date' => today()->toDateString(), 'start_time' => '09:00']);

        $this->actingAs($this->admin)->post('/admin/rosters/generate', $this->payload(['to' => today()->toDateString()]));

        $this->assertSame(7, RosterSlot::count());
        $this->assertSame(6, RosterSlot::where('doctor_id', $this->doctor->id)->count());
    }

    public function test_validation_rules(): void
    {
        $post = fn (array $overrides) => $this->actingAs($this->admin)->post('/admin/rosters/generate', $this->payload($overrides));

        $post(['from' => today()->subDay()->toDateString()])->assertSessionHasErrors('from');
        $post(['to' => today()->subDay()->toDateString()])->assertSessionHasErrors('to');
        $post(['to' => today()->addDays(RosterGenerator::MAX_DAYS)->toDateString()])->assertSessionHasErrors('to');
        $post(['days' => []])->assertSessionHasErrors('days');
        $post(['days' => [8]])->assertSessionHasErrors('days.0');
        $post(['work_end' => '09:00'])->assertSessionHasErrors('work_end');
        $post(['duration' => 25])->assertSessionHasErrors('duration');
        $post(['doctor_id' => $this->admin->id])->assertSessionHasErrors('doctor_id');
        $post(['break_start' => '12:00', 'break_end' => ''])->assertSessionHasErrors('break_end');
        $post(['break_start' => '12:00', 'break_end' => '11:00'])->assertSessionHasErrors('break_end');
        $post(['break_start' => '08:00', 'break_end' => '08:30'])->assertSessionHasErrors('break_start');
        $post(['break_start' => '11:00', 'break_end' => '13:00'])->assertSessionHasErrors('break_end');

        $this->assertSame(0, RosterSlot::count());
    }

    public function test_the_longest_allowed_range_is_accepted(): void
    {
        $this->actingAs($this->admin)->post('/admin/rosters/generate', $this->payload([
            'to' => today()->addDays(RosterGenerator::MAX_DAYS - 1)->toDateString(),
            'days' => [1],
            'work_end' => '10:00',
        ]))->assertSessionHasNoErrors();

        // 62 days starting on a Monday hold 9 Mondays, two sessions each.
        $this->assertSame(18, RosterSlot::count());
    }

    public function test_a_pattern_that_would_create_too_many_slots_is_refused(): void
    {
        $this->actingAs($this->admin)->post('/admin/rosters/generate', $this->payload([
            'to' => today()->addDays(RosterGenerator::MAX_DAYS - 1)->toDateString(),
            'days' => [1, 2, 3, 4, 5, 6, 7],
            'work_start' => '06:00',
            'work_end' => '22:00',
            'duration' => 15,
        ]))->assertSessionHasErrors('duration');

        $this->assertSame(0, RosterSlot::count());
    }

    public function test_generated_slots_can_be_booked_by_patients(): void
    {
        \App\Models\DoctorProfile::factory()->create(['user_id' => $this->doctor->id]);

        $this->actingAs($this->admin)->post('/admin/rosters/generate', $this->payload(['from' => today()->addDay()->toDateString()]));

        $this->assertGreaterThan(0, RosterSlot::bookable()->count());
    }

    public function test_buttons_to_open_the_generator_appear_on_both_roster_pages(): void
    {
        $this->actingAs($this->admin)->get('/admin/rosters')->assertSee(route('admin.rosters.generate'), false);
        $this->actingAs($this->admin)->get('/admin/rosters/calendar')->assertSee(route('admin.rosters.generate'), false);
    }
}
