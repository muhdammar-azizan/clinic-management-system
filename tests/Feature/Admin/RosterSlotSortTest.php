<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\RosterSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TC-004 from the SQA report: search the roster list and sort it.
 */
class RosterSlotSortTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => Role::Admin]);
    }

    /**
     * @return list<int> slot ids in the order the list shows them
     */
    private function order(string $query = ''): array
    {
        return $this->actingAs($this->admin)->get('/admin/rosters?'.$query)
            ->assertOk()
            ->viewData('slots')->pluck('id')->all();
    }

    private function doctor(string $name): User
    {
        return User::factory()->create(['role' => Role::Doctor, 'name' => $name]);
    }

    public function test_default_order_is_newest_date_first(): void
    {
        $doctor = $this->doctor('Dr. Any');
        $old = RosterSlot::factory()->create(['doctor_id' => $doctor->id, 'slot_date' => '2026-12-01']);
        $new = RosterSlot::factory()->create(['doctor_id' => $doctor->id, 'slot_date' => '2026-12-20']);

        $this->assertSame([$new->id, $old->id], $this->order());
    }

    public function test_sort_by_date_oldest_first(): void
    {
        $doctor = $this->doctor('Dr. Any');
        $old = RosterSlot::factory()->create(['doctor_id' => $doctor->id, 'slot_date' => '2026-12-01']);
        $new = RosterSlot::factory()->create(['doctor_id' => $doctor->id, 'slot_date' => '2026-12-20']);

        $this->assertSame([$old->id, $new->id], $this->order('sort=date_asc'));
    }

    public function test_sort_by_latest_added(): void
    {
        $doctor = $this->doctor('Dr. Any');
        $first = RosterSlot::factory()->create(['doctor_id' => $doctor->id, 'slot_date' => '2026-12-20']);
        $second = RosterSlot::factory()->create(['doctor_id' => $doctor->id, 'slot_date' => '2026-12-01']);

        $this->assertSame([$second->id, $first->id], $this->order('sort=latest'));
    }

    public function test_sort_by_doctor_name_a_to_z(): void
    {
        $zul = RosterSlot::factory()->create(['doctor_id' => $this->doctor('Dr. Zulkifli')->id, 'slot_date' => '2026-12-01']);
        $aisyah = RosterSlot::factory()->create(['doctor_id' => $this->doctor('Dr. Aisyah')->id, 'slot_date' => '2026-12-02']);
        $mei = RosterSlot::factory()->create(['doctor_id' => $this->doctor('Dr. Mei')->id, 'slot_date' => '2026-12-03']);

        $this->assertSame([$aisyah->id, $mei->id, $zul->id], $this->order('sort=doctor_az'));
    }

    public function test_doctor_sort_orders_one_doctors_slots_by_date_then_time(): void
    {
        $doctor = $this->doctor('Dr. Aisyah');
        $late = RosterSlot::factory()->create(['doctor_id' => $doctor->id, 'slot_date' => '2026-12-05', 'start_time' => '14:00']);
        $early = RosterSlot::factory()->create(['doctor_id' => $doctor->id, 'slot_date' => '2026-12-05', 'start_time' => '09:00']);
        $nextDay = RosterSlot::factory()->create(['doctor_id' => $doctor->id, 'slot_date' => '2026-12-06', 'start_time' => '08:00']);

        $this->assertSame([$early->id, $late->id, $nextDay->id], $this->order('sort=doctor_az'));
    }

    public function test_sort_works_together_with_search_and_keeps_both_in_pagination_links(): void
    {
        $doctor = $this->doctor('Dr. Aisyah');
        foreach (range(1, 16) as $day) {
            RosterSlot::factory()->create([
                'doctor_id' => $doctor->id,
                'slot_date' => '2026-12-'.str_pad((string) $day, 2, '0', STR_PAD_LEFT),
                'start_time' => '09:00',
            ]);
        }
        RosterSlot::factory()->create(['doctor_id' => $this->doctor('Dr. Other')->id]);

        $response = $this->actingAs($this->admin)->get('/admin/rosters?search=aisyah&sort=date_asc');

        $this->assertSame(15, $response->viewData('slots')->count());
        $this->assertSame(16, $response->viewData('slots')->total());
        $response->assertSee('search=aisyah&amp;sort=date_asc&amp;page=2', false);
    }

    public function test_unknown_sort_value_is_rejected(): void
    {
        $this->actingAs($this->admin)->get('/admin/rosters?sort=hack')->assertSessionHasErrors('sort');
    }

    public function test_sort_dropdown_lists_every_option_and_marks_the_current_one(): void
    {
        $this->actingAs($this->admin)->get('/admin/rosters?sort=doctor_az')
            ->assertSee('Doctor A-Z')
            ->assertSee('Latest added')
            ->assertSee('Date, oldest first')
            ->assertSee('<option value="doctor_az" selected>', false);
    }

    public function test_choosing_a_sort_alone_is_not_shown_as_a_filter(): void
    {
        RosterSlot::factory()->create();

        $this->actingAs($this->admin)->get('/admin/rosters?sort=latest')
            ->assertDontSee('>Clear<', false);
    }

    public function test_filters_apply_automatically_without_a_button_click(): void
    {
        $html = $this->actingAs($this->admin)->get('/admin/rosters')->getContent();

        $this->assertStringContainsString('input.debounce.400ms', $html);
        $this->assertStringContainsString('requestSubmit()', $html);
    }
}
