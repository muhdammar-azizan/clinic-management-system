<?php

namespace Tests\Feature;

use App\Models\User;
use App\Providers\AppServiceProvider;
use App\Services\HealthCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\URL;
use RuntimeException;
use Tests\TestCase;

class DeployReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_reports_ok_without_login(): void
    {
        $this->getJson('/health')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJson(['status' => 'ok', 'checks' => ['database' => 'ok', 'storage' => 'ok']]);
    }

    public function test_health_endpoint_does_not_start_a_session(): void
    {
        $response = $this->get('/health');

        $this->assertCount(0, $response->headers->getCookies());
    }

    public function test_health_check_fails_without_leaking_the_error(): void
    {
        $check = new HealthCheck(fn () => throw new RuntimeException('password=secret host=db.internal'));

        $result = $check->run();

        $this->assertSame('fail', $result['status']);
        $this->assertSame('fail', $result['checks']['database']);
        $this->assertStringNotContainsString('secret', json_encode($result));
    }

    public function test_health_endpoint_returns_503_when_a_check_fails(): void
    {
        $this->app->instance(HealthCheck::class, new HealthCheck(fn () => throw new RuntimeException('down')));

        $this->getJson('/health')
            ->assertStatus(503)
            ->assertJson(['status' => 'fail', 'checks' => ['database' => 'fail']]);
    }

    public function test_demo_seed_fills_an_empty_database_once(): void
    {
        $this->assertSame(0, User::count());

        Artisan::call('demo:seed');
        $users = User::count();
        $this->assertGreaterThan(0, $users);

        Artisan::call('demo:seed');
        $this->assertSame($users, User::count());
    }

    public function test_demo_seed_never_touches_a_database_that_has_data(): void
    {
        User::factory()->create(['email' => 'existing@clinic.test']);

        Artisan::call('demo:seed');

        $this->assertSame(1, User::count());
    }

    public function test_links_use_https_when_forced(): void
    {
        config(['app.force_https' => true]);
        $this->app->getProvider(AppServiceProvider::class)->boot();

        $this->assertStringStartsWith('https://', URL::to('/login'));
    }
}
