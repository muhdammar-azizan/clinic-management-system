<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_verified_admin_who_can_log_in(): void
    {
        $this->artisan('create:admin')
            ->expectsQuestion('Full name', 'Clinic Owner')
            ->expectsQuestion('Email address', 'owner@clinic.test')
            ->expectsQuestion('Password (typing is hidden)', 'a-strong-password-1')
            ->expectsQuestion('Repeat the password', 'a-strong-password-1')
            ->expectsOutputToContain('Admin account created for owner@clinic.test')
            ->assertSuccessful();

        $admin = User::where('email', 'owner@clinic.test')->firstOrFail();

        $this->assertSame(Role::Admin, $admin->role);
        $this->assertNotNull($admin->email_verified_at);
        $this->assertTrue(Hash::check('a-strong-password-1', $admin->password));

        $this->post('/login', ['email' => 'owner@clinic.test', 'password' => 'a-strong-password-1'])
            ->assertRedirect('/dashboard');
    }

    public function test_name_and_email_can_be_given_as_options_but_the_password_is_still_asked(): void
    {
        $this->artisan('create:admin', ['--name' => 'Clinic Owner', '--email' => 'owner@clinic.test'])
            ->expectsQuestion('Password (typing is hidden)', 'a-strong-password-1')
            ->expectsQuestion('Repeat the password', 'a-strong-password-1')
            ->assertSuccessful();

        $this->assertSame(1, User::where('role', Role::Admin)->count());
    }

    public function test_it_refuses_an_email_that_is_already_used(): void
    {
        User::factory()->create(['email' => 'owner@clinic.test']);

        $this->artisan('create:admin')
            ->expectsQuestion('Full name', 'Clinic Owner')
            ->expectsQuestion('Email address', 'owner@clinic.test')
            ->expectsQuestion('Password (typing is hidden)', 'a-strong-password-1')
            ->expectsQuestion('Repeat the password', 'a-strong-password-1')
            ->assertFailed();

        $this->assertSame(1, User::count());
    }

    public function test_it_refuses_a_weak_or_mismatched_password(): void
    {
        $this->artisan('create:admin')
            ->expectsQuestion('Full name', 'Clinic Owner')
            ->expectsQuestion('Email address', 'owner@clinic.test')
            ->expectsQuestion('Password (typing is hidden)', 'short')
            ->expectsQuestion('Repeat the password', 'different')
            ->assertFailed();

        $this->assertSame(0, User::count());
    }

    public function test_it_refuses_a_badly_formed_email(): void
    {
        $this->artisan('create:admin')
            ->expectsQuestion('Full name', 'Clinic Owner')
            ->expectsQuestion('Email address', 'not-an-email')
            ->expectsQuestion('Password (typing is hidden)', 'a-strong-password-1')
            ->expectsQuestion('Repeat the password', 'a-strong-password-1')
            ->assertFailed();

        $this->assertSame(0, User::count());
    }
}
