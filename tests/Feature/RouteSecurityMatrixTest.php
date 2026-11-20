<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Appointment;
use App\Models\DoctorProfile;
use App\Models\MedicalRecord;
use App\Models\MedicalRecordAttachment;
use App\Models\RosterSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Tests\TestCase;

/**
 * Calls every role-protected route as a guest and as every wrong role.
 * New routes are covered automatically, so a forgotten `role:` middleware fails this test.
 */
class RouteSecurityMatrixTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, int> route parameter name => id of a real model */
    private array $ids;

    protected function setUp(): void
    {
        parent::setUp();

        $appointment = Appointment::factory()->create();
        $record = MedicalRecord::factory()->create();

        $this->ids = [
            'doctor' => DoctorProfile::factory()->create()->user_id,
            'patient' => $appointment->patient_id,
            'slot' => RosterSlot::factory()->create()->id,
            'appointment' => $appointment->id,
            'record' => $record->id,
            'attachment' => MedicalRecordAttachment::factory()->create(['medical_record_id' => $record->id])->id,
        ];
    }

    /**
     * Role-protected routes as [route, required role].
     *
     * @return list<array{Route, string}>
     */
    private function protectedRoutes(): array
    {
        $found = [];

        foreach (RouteFacade::getRoutes() as $route) {
            foreach ($route->gatherMiddleware() as $middleware) {
                if (is_string($middleware) && str_starts_with($middleware, 'role:')) {
                    $found[] = [$route, substr($middleware, 5)];
                }
            }
        }

        return $found;
    }

    public function test_the_matrix_covers_a_meaningful_number_of_routes(): void
    {
        $this->assertGreaterThan(40, count($this->protectedRoutes()));
    }

    public function test_guests_are_sent_to_login_on_every_protected_route(): void
    {
        foreach ($this->protectedRoutes() as [$route]) {
            $method = strtolower($route->methods()[0]);
            $uri = '/'.$route->uri();
            foreach ($this->ids as $name => $id) {
                $uri = str_replace('{'.$name.'}', (string) $id, $uri);
            }

            $response = $method === 'get' ? $this->get($uri) : $this->{$method.'Json'}($uri);

            // JSON requests get 401, normal ones are redirected to the login page.
            $this->assertTrue(
                $response->status() === 401 || ($response->status() === 302 && str_contains($response->headers->get('Location'), '/login')),
                strtoupper($method)." {$uri} was not protected from guests (status {$response->status()})."
            );
        }
    }

    public function test_every_wrong_role_gets_403_on_every_protected_route(): void
    {
        $users = collect(Role::cases())->mapWithKeys(fn (Role $role) => [$role->value => User::factory()->create(['role' => $role])]);

        foreach ($this->protectedRoutes() as [$route, $allowed]) {
            $uri = '/'.$route->uri();
            foreach ($this->ids as $name => $id) {
                $uri = str_replace('{'.$name.'}', (string) $id, $uri);
            }
            $method = strtolower($route->methods()[0]);

            foreach ($users->except($allowed) as $role => $user) {
                $this->actingAs($user);
                $response = $method === 'get' ? $this->get($uri) : $this->{$method.'Json'}($uri);

                $this->assertSame(403, $response->status(), strtoupper($method)." {$uri} should be forbidden for {$role} (allowed: {$allowed}), got {$response->status()}.");
            }
        }
    }

    public function test_every_unprefixed_route_is_intentionally_public_or_auth_only(): void
    {
        $allowed = ['/', 'style-guide', 'dashboard', 'profile', 'feedback', 'login', 'register', 'forgot-password', 'reset-password/{token}', 'reset-password',
            'verify-email', 'verify-email/{id}/{hash}', 'email/verification-notification', 'confirm-password', 'password', 'logout', 'up', 'storage/{path}'];

        foreach (RouteFacade::getRoutes() as $route) {
            $hasRole = collect($route->gatherMiddleware())->contains(fn ($m) => is_string($m) && str_starts_with($m, 'role:'));

            if (! $hasRole) {
                $this->assertContains($route->uri(), $allowed, "Route [{$route->uri()}] has no role middleware and is not on the allowed list.");
            }
        }
    }

    public function test_private_medical_files_cannot_be_opened_by_url(): void
    {
        // The private disk serves files only through signed URLs, never by a plain path.
        $this->get('/storage/medical-records/1/anything.pdf')->assertStatus(403);
        $this->put('/storage/medical-records/1/anything.pdf')->assertStatus(403);
    }
}
