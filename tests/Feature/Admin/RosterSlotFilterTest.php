<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\DoctorProfile;
use App\Models\RosterSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RosterSlotFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => Role::Admin]);
    }

    private function search(string $query)
    {
        return $this->actingAs($this->admin)->get('/admin/rosters?'.$query);
    }

    /**
     * Slot IDs only appear in the table, so they are a reliable way to check which rows are listed.
     */
    private function code(RosterSlot $slot): string
    {
        return $slot->slot_code;
    }

    public function test_filter_by_doctor(): void
    {
        $a = RosterSlot::factory()->create();
        $b = RosterSlot::factory()->create();

        $this->search('doctor='.$a->doctor_id)
            ->assertSee($this->code($a))
            ->assertDontSee($this->code($b));
    }

    public function test_filter_by_service(): void
    {
        $profileA = DoctorProfile::factory()->create();
        $profileB = DoctorProfile::factory()->create();
        $a = RosterSlot::factory()->create(['doctor_id' => $profileA->user_id]);
        $b = RosterSlot::factory()->create(['doctor_id' => $profileB->user_id]);

        $this->search('service='.$profileA->service_id)
            ->assertSee($this->code($a))
            ->assertDontSee($this->code($b));
    }

    public function test_filter_by_status(): void
    {
        $free = RosterSlot::factory()->create();
        $taken = RosterSlot::factory()->occupied()->create();

        $this->search('status=occupied')
            ->assertSee($this->code($taken))
            ->assertDontSee($this->code($free));
    }

    public function test_filter_by_date_range(): void
    {
        $early = RosterSlot::factory()->create(['slot_date' => '2026-12-01']);
        $inside = RosterSlot::factory()->create(['slot_date' => '2026-12-10']);
        $late = RosterSlot::factory()->create(['slot_date' => '2026-12-31']);

        $this->search('from=2026-12-05&to=2026-12-20')
            ->assertSee($this->code($inside))
            ->assertDontSee($this->code($early))
            ->assertDontSee($this->code($late));
    }

    public function test_date_range_includes_both_end_dates(): void
    {
        $first = RosterSlot::factory()->create(['slot_date' => '2026-12-05']);
        $last = RosterSlot::factory()->create(['slot_date' => '2026-12-20']);

        $this->search('from=2026-12-05&to=2026-12-20')
            ->assertSee($this->code($first))
            ->assertSee($this->code($last));
    }

    public function test_search_by_slot_id_in_different_formats(): void
    {
        $target = RosterSlot::factory()->create();
        $other = RosterSlot::factory()->create();

        foreach ([$target->slot_code, strtolower($target->slot_code), (string) $target->id] as $term) {
            $this->search('search='.urlencode($term))
                ->assertSee($this->code($target))
                ->assertDontSee($this->code($other));
        }
    }

    public function test_search_by_doctor_name_and_notes(): void
    {
        $doctor = User::factory()->create(['role' => Role::Doctor, 'name' => 'Dr. Zulkifli Hamid']);
        $byName = RosterSlot::factory()->create(['doctor_id' => $doctor->id]);
        $byNotes = RosterSlot::factory()->create(['notes' => 'Home visit in Kuantan']);
        $none = RosterSlot::factory()->create();

        $this->search('search=zulkifli')->assertSee($this->code($byName))->assertDontSee($this->code($none));
        $this->search('search=kuantan')->assertSee($this->code($byNotes))->assertDontSee($this->code($none));
    }

    public function test_filters_combine(): void
    {
        $match = RosterSlot::factory()->occupied()->create(['slot_date' => '2026-12-10']);
        $wrongStatus = RosterSlot::factory()->create(['doctor_id' => $match->doctor_id, 'slot_date' => '2026-12-10', 'start_time' => '16:00']);

        $this->search("doctor={$match->doctor_id}&status=occupied&from=2026-12-01")
            ->assertSee($this->code($match))
            ->assertDontSee($this->code($wrongStatus));
    }

    public function test_invalid_filters_are_rejected(): void
    {
        $this->search('status=maybe')->assertSessionHasErrors('status');
        $this->search('from=2026-12-20&to=2026-12-01')->assertSessionHasErrors('to');
    }

    public function test_empty_result_shows_a_filter_message_and_clear_link(): void
    {
        RosterSlot::factory()->create();

        $this->search('search=nothing-matches-this')
            ->assertSee('No slots match your filters.')
            ->assertSee('Clear');
    }

    public function test_filters_survive_pagination(): void
    {
        $doctor = User::factory()->create(['role' => Role::Doctor]);

        foreach (range(1, 16) as $day) {
            RosterSlot::factory()->create([
                'doctor_id' => $doctor->id,
                'slot_date' => '2026-12-'.str_pad((string) $day, 2, '0', STR_PAD_LEFT),
                'start_time' => '09:00',
            ]);
        }

        $this->search('doctor='.$doctor->id)
            ->assertSee('doctor='.$doctor->id.'&amp;page=2', false);
    }
}
