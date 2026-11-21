<?php

namespace Tests\Quality;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Base class for the tests that come from the SQA reports.
 * Test names carry the report's test case id (TC-001) and groups carry the SRS requirement ids,
 * so a result can be traced back to the report. Run one with: --group=TC-001
 */
abstract class QualityTestCase extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['role' => Role::Admin]);
    }

    protected function doctor(?string $name = null): User
    {
        return User::factory()->create(array_filter(['role' => Role::Doctor, 'name' => $name]));
    }

    protected function patient(): User
    {
        return User::factory()->create();
    }
}
