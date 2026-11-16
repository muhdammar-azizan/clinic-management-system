<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_redirects_user_to_their_role_dashboard(): void
    {
        foreach (Role::cases() as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->get('/dashboard')
                ->assertRedirect(route($role->dashboardRoute()));
        }
    }

    public function test_user_cannot_access_another_role_area(): void
    {
        $patient = User::factory()->create(['role' => Role::Patient]);

        $this->actingAs($patient)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($patient)->get('/doctor/dashboard')->assertForbidden();
        $this->actingAs($patient)->get('/patient/dashboard')->assertOk();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }
}
