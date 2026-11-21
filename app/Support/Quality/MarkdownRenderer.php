<?php

namespace App\Support\Quality;

/**
 * Writes the generated part of QUALITY.md from QualityReport::build().
 * Nothing here depends on the current time, so the same results always give the same text.
 */
class MarkdownRenderer
{
    public const START = '<!-- quality:generated:start -->';

    public const END = '<!-- quality:generated:end -->';

    /**
     * @param  array<string, mixed>  $report  from QualityReport::build()
     */
    public function render(array $report): string
    {
        $parts = [
            '_This block is written by `php artisan quality:document` from `quality/results.json` and `quality/load-test.json`. Do not edit it by hand._',
            $this->summary($report),
            $this->metrics($report, 3, 'Module 3: Doctor Roster'),
            $this->metrics($report, 4, 'Module 4: Medical Records'),
            $this->testCases($report),
            $this->requirements($report),
            $this->checks($report),
            $this->load($report['load']),
            $this->deviations($report['deviations']),
        ];

        return implode("\n\n", $parts);
    }

    /**
     * Put the generated text between the markers of an existing document.
     */
    public function replaceBlock(string $document, string $generated): string
    {
        $start = strpos($document, self::START);
        $end = strpos($document, self::END);

        if ($start === false || $end === false || $end < $start) {
            throw new \InvalidArgumentException('The document needs the start and end markers.');
        }

        return substr($document, 0, $start + strlen(self::START))
            ."\n\n".$generated."\n\n"
            .substr($document, $end);
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function summary(array $report): string
    {
        if (! $report['has_results']) {
            return "### Summary\n\nNo results have been saved yet. Run `php artisan quality:report`.";
        }

        $summary = $report['summary'];
        $environment = $report['environment'] ?? [];
        $rows = [
            ['Quality tests passed', "{$summary['passed']} of {$summary['total']}".($summary['failed'] > 0 ? " ({$summary['failed']} failed)" : '')],
        ];

        if ($summary['full_suite']) {
            $rows[] = ['Whole test suite', "{$summary['full_suite']['passed']} of {$summary['full_suite']['total']} passed"];
        }

        $rows[] = ['Results saved', substr((string) $report['generated_at'], 0, 10)];
        $rows[] = ['Commit', '`'.($environment['commit'] ?? 'unknown').'`'];
        $rows[] = ['Runtime', 'PHP '.($environment['php'] ?? '?').', Laravel '.($environment['laravel'] ?? '?')];
        $rows[] = ['Test database', $environment['database'] ?? 'unknown'];

        return "### Summary\n\n".$this->table(['Item', 'Value'], $rows);
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function metrics(array $report, int $module, string $title): string
    {
        $rows = collect($report['metrics'])->where('module', $module)->map(fn ($m) => [
            $m['attribute'], $m['name'], $m['target'], $m['value'] ?? '-', $this->label($m['status']),
        ])->all();

        return "### Metrics, {$title}\n\n".$this->table(['Attribute', 'Metric', 'Target', 'Result', 'Status'], $rows);
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function testCases(array $report): string
    {
        $rows = array_map(fn ($c) => [
            $c['id'], $c['attribute'], $c['title'], $this->numbers($c['requirements'], 'SRS-REQ-') ?: '-', (string) $c['tests'], $this->label($c['status']),
        ], $report['test_cases']);

        return "### Module 3 test cases\n\n".$this->table(['ID', 'Attribute', 'Scenario', 'Requirements', 'Automated checks', 'Status'], $rows);
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function requirements(array $report): string
    {
        $rows = array_map(fn ($r) => [
            str_replace('SRS-REQ-', '', $r['id']), $r['title'], $this->numbers($r['cases'], 'TC-') ?: '-', $this->label($r['status']),
        ], $report['requirements']);

        return "### Requirement coverage (SRS-REQ-301 to 312)\n\n".$this->table(['Requirement', 'Meaning', 'Test cases', 'Status'], $rows);
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function checks(array $report): string
    {
        $rows = array_map(fn ($c) => [$c['id'], $c['attribute'], $c['title'], (string) $c['tests'], $this->label($c['status'])], $report['checks']);

        return "### Module 4 checks\n\n".$this->table(['ID', 'Attribute', 'Check', 'Tests', 'Status'], $rows);
    }

    /**
     * @param  array<string, mixed>|null  $load
     */
    private function load(?array $load): string
    {
        if ($load === null) {
            return "### Load test\n\nNo load test has been saved yet. Run `php artisan quality:load-test`.";
        }

        $rows = array_map(fn ($s) => [
            $s['label'], (string) $s['mean_ms'], (string) $s['p95_ms'], (string) $s['requests_per_second'],
            $s['requests_per_second'] > 0 ? (string) round(1000 / $s['requests_per_second']) : '-', (string) ($s['failed'] + $s['non_2xx']),
        ], $load['scenarios']);

        $data = $load['data'];

        return "### Load test\n\n"
            ."{$load['tool']} against {$load['base_url']}. Server: {$load['server']}. "
            .'Data: '.number_format($data['medical_records']).' medical records, '.number_format($data['roster_slots']).' roster slots, '.number_format($data['appointments']).' appointments.'
            ."\n\n".$this->table(['Page', 'Mean (ms)', '95% within (ms)', 'Requests/s', 'One request (ms)', 'Failed'], $rows);
    }

    /**
     * @param  list<array{0: string, 1: string}>  $deviations
     */
    private function deviations(array $deviations): string
    {
        $lines = array_map(fn ($d) => "- **{$d[0]}:** {$d[1]}", $deviations);

        return "### Deviations and limits\n\n".implode("\n", $lines);
    }

    /**
     * @param  list<string>  $headers
     * @param  list<list<string>>  $rows
     */
    private function table(array $headers, array $rows): string
    {
        $cell = fn (string $text) => str_replace(['|', "\n"], ['\\|', ' '], $text);
        $line = fn (array $cells) => '| '.implode(' | ', array_map($cell, $cells)).' |';

        return implode("\n", [
            $line($headers),
            '|'.str_repeat(' --- |', count($headers)),
            ...array_map($line, $rows),
        ]);
    }

    private function label(string $status): string
    {
        return match ($status) {
            'met' => 'Met',
            'not_met' => 'Not met',
            'pass' => 'Pass',
            'fail' => 'Fail',
            default => 'No data',
        };
    }

    /**
     * "SRS-REQ-301", "SRS-REQ-302" becomes "301, 302".
     *
     * @param  list<string>  $ids
     */
    private function numbers(array $ids, string $prefix): string
    {
        return implode(', ', array_map(fn ($id) => str_replace($prefix, '', $id), $ids));
    }
}
