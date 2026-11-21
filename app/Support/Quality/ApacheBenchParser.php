<?php

namespace App\Support\Quality;

/**
 * Reads the text that ApacheBench (ab) prints at the end of a run.
 */
class ApacheBenchParser
{
    /**
     * @return array{requests: int, concurrency: int, failed: int, non_2xx: int, mean_ms: float, requests_per_second: float, p50_ms: ?int, p95_ms: ?int, max_ms: ?int}
     */
    public function parse(string $output): array
    {
        $number = fn (string $pattern): ?float => preg_match($pattern, $output, $m) ? (float) $m[1] : null;

        $requests = $number('/^Complete requests:\s+(\d+)/m');
        $mean = $number('/^Time per request:\s+([\d.]+) \[ms\] \(mean\)\s*$/m');

        if ($requests === null || $mean === null) {
            throw new \InvalidArgumentException('This does not look like ApacheBench output.');
        }

        return [
            'requests' => (int) $requests,
            'concurrency' => (int) ($number('/^Concurrency Level:\s+(\d+)/m') ?? 1),
            'failed' => (int) ($number('/^Failed requests:\s+(\d+)/m') ?? 0),
            'non_2xx' => (int) ($number('/^Non-2xx responses:\s+(\d+)/m') ?? 0),
            'mean_ms' => round($mean, 1),
            'requests_per_second' => round($number('/^Requests per second:\s+([\d.]+)/m') ?? 0.0, 2),
            'p50_ms' => $this->percentile($output, '50%'),
            'p95_ms' => $this->percentile($output, '95%'),
            'max_ms' => $this->percentile($output, '100%'),
        ];
    }

    private function percentile(string $output, string $label): ?int
    {
        return preg_match('/^\s*'.preg_quote($label, '/').'\s+(\d+)/m', $output, $match) ? (int) $match[1] : null;
    }
}
