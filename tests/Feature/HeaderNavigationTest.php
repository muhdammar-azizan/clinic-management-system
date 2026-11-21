<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class HeaderNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_header_shows_the_user_name_role_and_dashboard_link(): void
    {
        foreach (Role::cases() as $role) {
            $user = User::factory()->create(['role' => $role, 'name' => 'Jane Doe']);

            $this->actingAs($user)
                ->get(route($role->dashboardRoute()))
                ->assertOk()
                ->assertSee('Jane Doe')
                ->assertSee('Role: '.$role->label())
                ->assertSee(route($role->dashboardRoute()), false);
        }
    }

    public function test_header_hides_menu_items_whose_routes_do_not_exist_yet(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);

        config(['navigation.admin' => [
            ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'active' => 'admin.dashboard'],
            ['label' => 'Future Page', 'route' => 'admin.future.index', 'active' => 'admin.future.*'],
        ]]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertSee('Dashboard')
            ->assertDontSee('Future Page');
    }

    public function test_every_menu_item_points_to_a_real_route(): void
    {
        foreach (config('navigation') as $role => $items) {
            foreach ($items as $item) {
                $this->assertTrue(Route::has($item['route']), "Missing route {$item['route']} for {$role}");
            }
        }
    }
}
