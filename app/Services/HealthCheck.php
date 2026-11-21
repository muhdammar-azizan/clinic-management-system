<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Answers one question for a hosting platform: can this instance do its job right now?
 * The result only says ok or failed for each part. Error messages never leave the server,
 * because the page is public.
 */
class HealthCheck
{
    /**
     * @param  (callable(): mixed)|null  $databaseProbe  replaces the real database query, used by tests
     */
    public function __construct(private $databaseProbe = null) {}

    /**
     * @return array{status: string, checks: array{database: string, storage: string}}
     */
    public function run(): array
    {
        $checks = [
            'database' => $this->passes($this->databaseProbe ?? fn () => DB::select('select 1')),
            'storage' => $this->passes(fn () => is_writable(storage_path('framework')) && is_writable(Storage::disk('local')->path(''))),
        ];

        return [
            'status' => in_array(false, $checks, true) ? 'fail' : 'ok',
            'checks' => array_map(fn (bool $passed) => $passed ? 'ok' : 'fail', $checks),
        ];
    }

    /**
     * @param  callable(): mixed  $probe
     */
    private function passes(callable $probe): bool
    {
        try {
            return $probe() !== false;
        } catch (\Throwable) {
            return false;
        }
    }
}
