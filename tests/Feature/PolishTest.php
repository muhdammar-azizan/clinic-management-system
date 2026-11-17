<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\RosterSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PolishTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_page_shows_the_branded_404(): void
    {
        $this->get('/this-page-does-not-exist')
            ->assertNotFound()
            ->assertSee('Page not found')
            ->assertSee('Go to home')
            ->assertSee(config('app.name'))
            ->assertDontSee('Symfony');
    }

    public function test_forbidden_page_is_branded(): void
    {
        $patient = User::factory()->create();

        $this->actingAs($patient)->get('/admin/dashboard')
            ->assertForbidden()
            ->assertSee('Access denied')
            ->assertSee('You do not have permission');
    }

    public function test_every_error_template_renders(): void
    {
        foreach ([403, 404, 419, 429, 500, 503] as $code) {
            $html = view("errors.{$code}")->render();

            $this->assertStringContainsString((string) $code, $html);
            $this->assertStringContainsString('Go to home', $html);
        }
    }

    public function test_server_errors_do_not_offer_a_back_button(): void
    {
        $this->assertStringNotContainsString('Go back', view('errors.500')->render());
        $this->assertStringContainsString('Go back', view('errors.404')->render());
    }

    public function test_page_titles_follow_the_active_menu_item(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);

        $this->actingAs($admin)->get('/admin/doctors')->assertSee('<title>Doctors | '.config('app.name').'</title>', false);
        $this->actingAs($admin)->get('/admin/rosters/calendar')->assertSee('<title>Doctor Rosters | '.config('app.name').'</title>', false);
        $this->actingAs($admin)->get('/profile')->assertSee('<title>Profile | '.config('app.name').'</title>', false);
    }

    public function test_every_authenticated_menu_page_has_a_title(): void
    {
        foreach (Role::cases() as $role) {
            $user = User::factory()->create(['role' => $role]);

            foreach (config("navigation.{$role->value}") as $item) {
                $this->actingAs($user)->get(route($item['route']))
                    ->assertSee('<title>'.$item['label'].' | ', false);
            }
        }
    }

    public function test_login_and_register_have_titles(): void
    {
        $this->get('/login')->assertSee('<title>Sign in | ', false);
        $this->get('/register')->assertSee('<title>Register | ', false);
    }

    public function test_pagination_uses_the_clinic_style_and_keeps_filters(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $doctor = User::factory()->create(['role' => Role::Doctor]);
        foreach (range(1, 16) as $day) {
            RosterSlot::factory()->create([
                'doctor_id' => $doctor->id,
                'slot_date' => '2026-12-'.str_pad((string) $day, 2, '0', STR_PAD_LEFT),
                'start_time' => '09:00',
            ]);
        }

        $this->actingAs($admin)->get('/admin/rosters?doctor='.$doctor->id)
            ->assertSee('Showing')
            ->assertSeeInOrder(['Showing', '1', 'to', '15', 'of', '16', 'results'])
            ->assertSee('aria-current="page"', false)
            ->assertSee('rel="next"', false)
            ->assertSee('doctor='.$doctor->id.'&amp;page=2', false);

        $this->actingAs($admin)->get('/admin/rosters?doctor='.$doctor->id.'&page=2')
            ->assertSee('rel="prev"', false);
    }

    public function test_no_pagination_when_everything_fits_on_one_page(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        RosterSlot::factory()->count(3)->create();

        $this->actingAs($admin)->get('/admin/rosters')->assertDontSee('Showing');
    }

    public function test_tables_scroll_sideways_on_small_screens(): void
    {
        $this->blade('<x-table><x-slot name="head"><tr><th>A</th></tr></x-slot><tr><td>1</td></tr></x-table>')
            ->assertSee('overflow-x-auto', false);
    }

    public function test_the_style_guide_is_not_a_menu_item_and_stays_environment_gated(): void
    {
        $this->assertTrue(Route::has('style-guide'));

        $this->app['env'] = 'production';
        $this->get('/style-guide')->assertNotFound();
    }
}
