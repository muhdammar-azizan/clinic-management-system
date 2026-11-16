<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\DoctorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileDetailsTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Aisyah Rahman',
            'email' => 'aisyah@example.com',
            'phone' => '012-345 6789',
            'date_of_birth' => '1999-05-20',
            'gender' => 'female',
            'address' => '12 Jalan Bunga, Kuantan',
        ], $overrides);
    }

    public function test_patient_can_save_contact_details(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile', $this->payload())->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('012-345 6789', $user->phone);
        $this->assertSame('1999-05-20', $user->date_of_birth->toDateString());
        $this->assertSame('female', $user->gender);
    }

    public function test_contact_details_are_validated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile', $this->payload([
            'phone' => 'call me',
            'date_of_birth' => now()->addDay()->toDateString(),
            'gender' => 'other',
        ]))->assertSessionHasErrors(['phone', 'date_of_birth', 'gender']);
    }

    public function test_details_are_optional(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile', [
            'name' => 'Aisyah Rahman',
            'email' => 'aisyah@example.com',
        ])->assertSessionHasNoErrors();
    }

    public function test_role_cannot_be_changed_through_the_profile_form(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile', $this->payload(['role' => 'admin']));

        $this->assertSame(Role::Patient, $user->fresh()->role);
    }

    public function test_only_patients_see_the_delete_account_section(): void
    {
        $this->actingAs(User::factory()->create())->get('/profile')->assertSee('Delete Account');

        $this->actingAs(User::factory()->create(['role' => Role::Admin]))->get('/profile')->assertDontSee('Delete Account');
    }

    public function test_doctor_and_admin_cannot_delete_their_own_account(): void
    {
        foreach ([Role::Doctor, Role::Admin] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->delete('/profile', ['password' => 'password'])->assertForbidden();

            $this->assertNotNull($user->fresh());
        }
    }

    public function test_doctor_sees_service_and_license_read_only(): void
    {
        $profile = DoctorProfile::factory()->create(['license_number' => 'MMC-12345']);

        $this->actingAs($profile->user)->get('/profile')
            ->assertSee($profile->service->name)
            ->assertSee('MMC-12345');
    }
}
