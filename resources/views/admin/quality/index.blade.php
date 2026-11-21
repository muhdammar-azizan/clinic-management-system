<x-app-layout>
    @php
        $badge = fn (string $status) => match ($status) {
            'met', 'pass' => ['success', $status === 'met' ? 'Met' : 'Pass'],
            'not_met', 'fail' => ['danger', $status === 'not_met' ? 'Not met' : 'Fail'],
            default => ['neutral', 'No data'],
        };
        $summary = $report['summary'];
        $metrics = collect($report['metrics']);
        $load = $report['load'];
    @endphp

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <x-page-header title="Quality Report" subtitle="Test results and metrics from the SQA reports of Module 3 (Doctor Roster) and Module 4 (Medical Records)." />

        @unless ($report['has_results'])
            <x-alert variant="info">
                No results have been saved yet. Run <code class="font-semibold">php artisan quality:report</code> and
                <code class="font-semibold">php artisan quality:load-test</code>, then reload this page.
            </x-alert>
        @endunless

        {{-- Summary --}}
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <x-stat-card label="Quality tests" :value="$summary['passed'].' / '.$summary['total']" :hint="$summary['failed'] > 0 ? $summary['failed'].' failed' : 'all passed'" />
            <x-stat-card label="Whole test suite"
                         :value="$summary['full_suite'] ? $summary['full_suite']['passed'].' / '.$summary['full_suite']['total'] : '-'"
                         :hint="$summary['full_suite'] ? 'tests passed' : 'run quality:report --all'" />
            <x-stat-card label="Feedback score"
                         :value="$metrics->firstWhere('name', 'User Feedback Score')['value'] ?? '-'"
                         :hint="$feedbackCount.' responses'" :href="route('admin.feedback.index')" />
            <x-stat-card label="Last run"
                         :value="$report['generated_at'] ? \Illuminate\Support\Carbon::parse($report['generated_at'])->format('d M Y') : '-'"
                         :hint="$report['environment'] ? 'commit '.($report['environment']['commit'] ?? '?').' · PHP '.$report['environment']['php'] : null" />
        </div>

        {{-- Metrics --}}
        <div class="grid gap-6 lg:grid-cols-2">
            @foreach ([3 => 'Module 3: Doctor Roster (usability, accuracy, data integrity, security)', 4 => 'Module 4: Medical Records (performance, reliability, usability)'] as $number => $heading)
                <x-card :title="$heading" :padding="false">
                    <x-table>
                        <x-slot name="head">
                            <tr><th>Attribute</th><th>Metric</th><th>Target</th><th>Result</th><th>Status</th></tr>
                        </x-slot>
                        @foreach ($metrics->where('module', $number) as $metric)
                            @php([$variant, $label] = $badge($metric['status']))
                            <tr>
                                <td>{{ $metric['attribute'] }}</td>
                                <td>{{ $metric['name'] }}</td>
                                <td class="whitespace-nowrap text-muted">{{ $metric['target'] }}</td>
                                <td class="whitespace-nowrap font-medium text-ink">{{ $metric['value'] ?? '-' }}</td>
                                <td><x-badge :variant="$variant" :label="$label" /></td>
                            </tr>
                        @endforeach
                    </x-table>
                </x-card>
            @endforeach
        </div>

        {{-- Module 3 test cases --}}
        <x-card title="Module 3 test cases (TC-001 to TC-013)" :padding="false">
            <x-table>
                <x-slot name="head">
                    <tr><th>ID</th><th>Attribute</th><th>Scenario</th><th>Requirements</th><th>Automated checks</th><th>Status</th></tr>
                </x-slot>
                @foreach ($report['test_cases'] as $case)
                    @php([$variant, $label] = $badge($case['status']))
                    <tr>
                        <td class="whitespace-nowrap font-medium">{{ $case['id'] }}</td>
                        <td>{{ $case['attribute'] }}</td>
                        <td>{{ $case['title'] }}</td>
                        <td class="text-xs text-muted">{{ collect($case['requirements'])->map(fn ($r) => str_replace('SRS-REQ-', '', $r))->implode(', ') ?: '-' }}</td>
                        <td>{{ $case['tests'] }}</td>
                        <td><x-badge :variant="$variant" :label="$label" /></td>
                    </tr>
                @endforeach
            </x-table>
        </x-card>

        <div class="grid gap-6 lg:grid-cols-2">
            {{-- Module 4 checks --}}
            <x-card title="Module 4 checks" :padding="false">
                <x-table>
                    <x-slot name="head">
                        <tr><th>Attribute</th><th>Check</th><th>Tests</th><th>Status</th></tr>
                    </x-slot>
                    @foreach ($report['checks'] as $check)
                        @php([$variant, $label] = $badge($check['status']))
                        <tr>
                            <td>{{ $check['attribute'] }}</td>
                            <td>{{ $check['title'] }}</td>
                            <td>{{ $check['tests'] }}</td>
                            <td><x-badge :variant="$variant" :label="$label" /></td>
                        </tr>
                    @endforeach
                </x-table>
            </x-card>

            {{-- Requirement coverage --}}
            <x-card title="Requirement coverage (SRS-REQ-301 to 312)" :padding="false">
                <x-table>
                    <x-slot name="head">
                        <tr><th>ID</th><th>Requirement</th><th>Test cases</th><th>Status</th></tr>
                    </x-slot>
                    @foreach ($report['requirements'] as $requirement)
                        @php([$variant, $label] = $badge($requirement['status']))
                        <tr>
                            <td class="whitespace-nowrap font-medium">{{ str_replace('SRS-REQ-', '', $requirement['id']) }}</td>
                            <td>{{ $requirement['title'] }}</td>
                            <td class="text-xs text-muted">{{ collect($requirement['cases'])->map(fn ($c) => str_replace('TC-', '', $c))->implode(', ') ?: '-' }}</td>
                            <td><x-badge :variant="$variant" :label="$label" /></td>
                        </tr>
                    @endforeach
                </x-table>
            </x-card>
        </div>

        {{-- Load test --}}
        <x-card title="Load test (ApacheBench)" :padding="false">
            @if ($load)
                <p class="border-b border-line px-5 py-3 text-sm text-muted">
                    {{ $load['tool'] }} on {{ $load['server'] }}.
                    Data: {{ number_format($load['data']['medical_records']) }} medical records,
                    {{ number_format($load['data']['roster_slots']) }} roster slots,
                    {{ number_format($load['data']['appointments']) }} appointments.
                    Run {{ \Illuminate\Support\Carbon::parse($load['generated_at'])->format('d M Y H:i') }}.
                </p>
                <x-table>
                    <x-slot name="head">
                        <tr><th>Page</th><th>Mean (ms)</th><th>50% within (ms)</th><th>95% within (ms)</th><th>Slowest (ms)</th><th>Requests/s</th><th>One request (ms)</th><th>Failed</th></tr>
                    </x-slot>
                    @foreach ($load['scenarios'] as $scenario)
                        <tr>
                            <td>{{ $scenario['label'] }}</td>
                            <td class="font-medium text-ink">{{ $scenario['mean_ms'] }}</td>
                            <td>{{ $scenario['p50_ms'] }}</td>
                            <td>{{ $scenario['p95_ms'] }}</td>
                            <td>{{ $scenario['max_ms'] }}</td>
                            <td>{{ $scenario['requests_per_second'] }}</td>
                            <td title="1000 divided by requests per second, which is the time one request takes when the server handles them one by one">{{ $scenario['requests_per_second'] > 0 ? round(1000 / $scenario['requests_per_second']) : '-' }}</td>
                            <td>{{ $scenario['failed'] + $scenario['non_2xx'] }}</td>
                        </tr>
                    @endforeach
                </x-table>
            @else
                <p class="px-5 py-4 text-sm text-muted">No load test saved yet. Start the app, then run <code class="font-semibold">php artisan quality:load-test</code>.</p>
            @endif
        </x-card>

        {{-- Honest notes --}}
        <x-card title="Deviations and limits">
            <ul class="space-y-2 text-sm text-ink">
                @foreach ($report['deviations'] as [$topic, $note])
                    <li class="flex gap-3"><span class="w-24 shrink-0 font-semibold text-deep">{{ $topic }}</span><span>{{ $note }}</span></li>
                @endforeach
            </ul>
        </x-card>

        {{-- Every automated check --}}
        <x-card title="All automated checks" :padding="false">
            <details class="group">
                <summary class="cursor-pointer px-5 py-3 text-sm font-medium text-primary">Show {{ count($report['tests']) }} checks</summary>
                <x-table>
                    <x-slot name="head">
                        <tr><th>Module</th><th>Attribute</th><th>Check</th><th>Groups</th><th>Time (s)</th><th>Status</th></tr>
                    </x-slot>
                    @foreach ($report['tests'] as $test)
                        @php([$variant, $label] = $badge($test['status'] === 'passed' ? 'pass' : ($test['status'] === 'failed' ? 'fail' : 'none')))
                        <tr>
                            <td>{{ $test['module'] ? 'M'.$test['module'] : '-' }}</td>
                            <td>{{ $test['attribute'] ?? '-' }}</td>
                            <td>{{ $test['title'] }}</td>
                            <td class="text-xs text-muted">{{ implode(', ', $test['groups']) }}</td>
                            <td>{{ $test['time'] }}</td>
                            <td><x-badge :variant="$variant" :label="$label" /></td>
                        </tr>
                    @endforeach
                </x-table>
            </details>
        </x-card>
    </div>
</x-app-layout>
