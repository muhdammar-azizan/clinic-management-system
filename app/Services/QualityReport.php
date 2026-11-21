<?php

namespace App\Services;

/**
 * Turns saved test and load test results into what the Quality page shows:
 * test case status, metrics against their targets, and requirement coverage.
 */
class QualityReport
{
    public function __construct(private ?string $path = null)
    {
        $this->path ??= (string) config('quality.path');
    }

    /** The saved test results, or null when quality:report has not been run yet. */
    public function results(): ?array
    {
        return $this->read('results.json');
    }

    /** The saved ApacheBench results, or null when quality:load-test has not been run yet. */
    public function loadTest(): ?array
    {
        return $this->read('load-test.json');
    }

    /**
     * @param  float|null  $feedbackScore  live average of the portal feedback, null when there is none
     * @return array<string, mixed>
     */
    public function build(?array $results, ?array $load, ?float $feedbackScore): array
    {
        $tests = $results['tests'] ?? [];

        return [
            'has_results' => $results !== null,
            'generated_at' => $results['generated_at'] ?? null,
            'environment' => $results['environment'] ?? null,
            'summary' => $this->summary($tests, $results),
            'test_cases' => $this->groupStatuses(config('quality.test_cases'), $tests),
            'checks' => $this->groupStatuses(config('quality.checks'), $tests),
            'requirements' => $this->requirements($tests),
            'metrics' => $this->metrics($tests, $load, $feedbackScore),
            'load' => $load,
            'tests' => $tests,
            'deviations' => config('quality.deviations'),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $tests
     * @return array{total: int, passed: int, failed: int, skipped: int, duration: float, full_suite: ?array}
     */
    private function summary(array $tests, ?array $results): array
    {
        $count = fn (string $status) => count(array_filter($tests, fn ($t) => $t['status'] === $status));

        return [
            'total' => count($tests),
            'passed' => $count('passed'),
            'failed' => $count('failed'),
            'skipped' => $count('skipped'),
            'duration' => round(array_sum(array_column($tests, 'time')), 1),
            'full_suite' => $results['full_suite'] ?? null,
        ];
    }

    /**
     * Status of each id: pass when every test in that group passed, fail when any failed, none when no test ran.
     *
     * @param  array<string, array{0: string, 1: string}>  $definitions
     * @param  list<array<string, mixed>>  $tests
     * @return list<array{id: string, attribute: string, title: string, status: string, tests: int, requirements: list<string>}>
     */
    private function groupStatuses(array $definitions, array $tests): array
    {
        $rows = [];

        foreach ($definitions as $id => [$attribute, $title]) {
            $inGroup = $this->inGroups($tests, [$id]);

            $rows[] = [
                'id' => $id,
                'attribute' => $attribute,
                'title' => $title,
                'status' => $this->statusOf($inGroup),
                'tests' => count($inGroup),
                'requirements' => $this->requirementIds($inGroup),
            ];
        }

        return $rows;
    }

    /**
     * Requirement coverage: which tests cover each SRS requirement and whether they pass.
     *
     * @param  list<array<string, mixed>>  $tests
     * @return list<array{id: string, title: string, status: string, tests: int, cases: list<string>}>
     */
    private function requirements(array $tests): array
    {
        $rows = [];

        foreach (config('quality.requirements') as $id => $title) {
            $inGroup = $this->inGroups($tests, [$id]);

            $cases = collect($inGroup)->pluck('groups')->flatten()
                ->filter(fn ($group) => str_starts_with($group, 'TC-'))->unique()->sort()->values()->all();

            $rows[] = ['id' => $id, 'title' => $title, 'status' => $this->statusOf($inGroup), 'tests' => count($inGroup), 'cases' => $cases];
        }

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $tests
     * @return list<array<string, mixed>>
     */
    private function metrics(array $tests, ?array $load, ?float $feedbackScore): array
    {
        $rows = [];

        foreach (config('quality.metrics') as $definition) {
            [$operator, $limit] = $definition['target'];
            $unit = match ($definition['kind']) {
                'pass_rate', 'fail_rate' => '%',
                'feedback' => ' / 3',
                'load_mean_ms' => ' ms',
                default => '',
            };

            $value = match ($definition['kind']) {
                'pass_rate' => $this->rate($this->inGroups($tests, $definition['groups']), 'passed'),
                'fail_rate' => $this->rate($this->inGroups($tests, $definition['groups']), 'failed'),
                'feedback' => $feedbackScore,
                'load_mean_ms' => $load ? max(array_column($load['scenarios'], 'mean_ms')) : null,
                'load_failed' => $load ? array_sum(array_map(fn ($s) => $s['failed'] + $s['non_2xx'], $load['scenarios'])) : null,
            };

            $rows[] = [
                'module' => $definition['module'],
                'attribute' => $definition['attribute'],
                'name' => $definition['name'],
                'target' => $operator.' '.$limit.$unit,
                'value' => $value === null ? null : round($value, 2).$unit,
                'status' => $value === null ? 'no_data' : ($this->compare($value, $operator, $limit) ? 'met' : 'not_met'),
            ];
        }

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $tests
     * @param  list<string>  $groups
     * @return list<array<string, mixed>>
     */
    private function inGroups(array $tests, array $groups): array
    {
        return array_values(array_filter($tests, fn ($test) => array_intersect($groups, $test['groups']) !== []));
    }

    /**
     * @param  list<array<string, mixed>>  $tests
     */
    private function statusOf(array $tests): string
    {
        return match (true) {
            $tests === [] => 'none',
            collect($tests)->contains(fn ($t) => $t['status'] === 'failed') => 'fail',
            collect($tests)->every(fn ($t) => $t['status'] === 'skipped') => 'none',
            default => 'pass',
        };
    }

    /**
     * @param  list<array<string, mixed>>  $tests
     * @return list<string>
     */
    private function requirementIds(array $tests): array
    {
        return collect($tests)->pluck('groups')->flatten()
            ->filter(fn ($group) => str_starts_with($group, 'SRS-REQ-'))->unique()->sort()->values()->all();
    }

    /**
     * @param  list<array<string, mixed>>  $tests
     */
    private function rate(array $tests, string $status): ?float
    {
        if ($tests === []) {
            return null;
        }

        return count(array_filter($tests, fn ($t) => $t['status'] === $status)) / count($tests) * 100;
    }

    private function compare(float|int $value, string $operator, float|int $limit): bool
    {
        return match ($operator) {
            '>' => $value > $limit,
            '>=' => $value >= $limit,
            '<' => $value < $limit,
            '<=' => $value <= $limit,
            default => abs($value - $limit) < 0.00001,
        };
    }

    private function read(string $file): ?array
    {
        $path = rtrim((string) $this->path, '/\\').DIRECTORY_SEPARATOR.$file;

        if (! is_file($path)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($path), true);

        return is_array($data) ? $data : null;
    }
}
