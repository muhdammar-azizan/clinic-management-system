<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Feedback;
use App\Models\User;
use App\Services\QualityReport;
use App\Support\Quality\ApacheBenchParser;
use App\Support\Quality\JunitParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class QualityReportTest extends TestCase
{
    use RefreshDatabase;

    private string $folder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->folder = sys_get_temp_dir().DIRECTORY_SEPARATOR.'quality-test-'.uniqid();
        File::ensureDirectoryExists($this->folder);
        config(['quality.path' => $this->folder]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->folder);

        parent::tearDown();
    }

    /**
     * @param  list<string>  $groups
     * @return array<string, mixed>
     */
    private function test(string $status, array $groups, string $class = 'Tests\\Quality\\Module3\\UsabilityTest'): array
    {
        return ['class' => $class, 'name' => 'test_x', 'title' => 'X', 'module' => 3, 'attribute' => 'Usability', 'status' => $status, 'time' => 0.1, 'groups' => $groups];
    }

    private function metric(array $report, string $name): array
    {
        return collect($report['metrics'])->firstWhere('name', $name);
    }

    // JUnit parser

    public function test_junit_results_become_a_plain_list_with_groups_module_and_attribute(): void
    {
        $xml = <<<'XML'
        <?xml version="1.0"?>
        <testsuites><testsuite name="Quality">
          <testcase name="test_tc005_a_past_date_is_not_saved" class="Tests\Quality\Module3\AccuracyTest" time="0.123456"/>
          <testcase name="test_tc008_a_duplicate_is_refused" class="Tests\Quality\Module3\DataIntegrityTest" time="0.5"><failure>boom</failure></testcase>
          <testcase name="test_m4_something" class="Tests\Quality\Module4\ReliabilityTest" time="1"><skipped/></testcase>
        </testsuite></testsuites>
        XML;

        $tests = (new JunitParser)->parse($xml, fn (string $class, string $method) => $method === 'test_tc005_a_past_date_is_not_saved' ? ['TC-005', 'SRS-REQ-309'] : []);

        $this->assertCount(3, $tests);
        $this->assertSame(['passed', 'failed', 'skipped'], array_column($tests, 'status'));
        $this->assertSame([3, 3, 4], array_column($tests, 'module'));
        $this->assertSame(['Accuracy', 'Data Integrity', 'Reliability'], array_column($tests, 'attribute'));
        $this->assertSame(['TC-005', 'SRS-REQ-309'], $tests[0]['groups']);
        $this->assertSame(0.123, $tests[0]['time']);
        $this->assertSame('A past date is not saved', $tests[0]['title']);
    }

    public function test_groups_are_read_from_the_real_test_classes(): void
    {
        $xml = '<testsuite><testcase name="test_tc006_a_start_time_that_has_passed_today_is_not_saved" class="Tests\Quality\Module3\AccuracyTest" time="0.1"/></testsuite>';

        $groups = (new JunitParser)->parse($xml)[0]['groups'];

        $this->assertContains('TC-006', $groups);
        $this->assertContains('SRS-REQ-309', $groups);
    }

    public function test_a_log_that_is_not_xml_is_refused(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        @(new JunitParser)->parse('not xml at all');
    }

    // ApacheBench parser

    public function test_apachebench_output_is_read(): void
    {
        $output = <<<'AB'
        Concurrency Level:      10
        Time taken for tests:   0.532 seconds
        Complete requests:      100
        Failed requests:        2
        Non-2xx responses:      1
        Requests per second:    187.97 [#/sec] (mean)
        Time per request:       53.204 [ms] (mean)
        Time per request:       5.320 [ms] (mean, across all concurrent requests)

        Percentage of the requests served within a certain time (ms)
          50%     50
          95%     80
         100%     99 (longest request)
        AB;

        $this->assertSame([
            'requests' => 100, 'concurrency' => 10, 'failed' => 2, 'non_2xx' => 1,
            'mean_ms' => 53.2, 'requests_per_second' => 187.97, 'p50_ms' => 50, 'p95_ms' => 80, 'max_ms' => 99,
        ], (new ApacheBenchParser)->parse($output));
    }

    public function test_apachebench_output_without_failures_defaults_to_zero(): void
    {
        $result = (new ApacheBenchParser)->parse("Concurrency Level: 5\nComplete requests:      50\nFailed requests:        0\nRequests per second:    10.00 [#/sec] (mean)\nTime per request:       500.000 [ms] (mean)\n");

        $this->assertSame(0, $result['non_2xx']);
        $this->assertNull($result['p95_ms']);
    }

    public function test_text_that_is_not_apachebench_output_is_refused(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new ApacheBenchParser)->parse('ab: command not found');
    }

    // Metrics

    public function test_metrics_are_worked_out_from_the_results_with_the_report_formulas(): void
    {
        $tests = [
            $this->test('passed', ['TC-001']), $this->test('passed', ['TC-002']), $this->test('passed', ['TC-003']), $this->test('failed', ['TC-004']),
        ];

        $report = (new QualityReport)->build(['tests' => $tests, 'generated_at' => now()->toIso8601String()], null, null);

        // 3 of 4 passed is 75%, which misses the "more than 90%" target.
        $completion = $this->metric($report, 'Task Completion Rate');
        $this->assertSame('75%', $completion['value']);
        $this->assertSame('> 90%', $completion['target']);
        $this->assertSame('not_met', $completion['status']);
    }

    public function test_a_metric_is_met_when_every_test_passes(): void
    {
        $tests = array_map(fn ($tc) => $this->test('passed', [$tc]), ['TC-005', 'TC-006', 'TC-007']);

        $report = (new QualityReport)->build(['tests' => $tests], null, null);

        $this->assertSame('100%', $this->metric($report, 'Accuracy Rate')['value']);
        $this->assertSame('met', $this->metric($report, 'Accuracy Rate')['status']);
        $this->assertSame('0%', $this->metric($report, 'Expired Slot Error Rate')['value']);
        $this->assertSame('met', $this->metric($report, 'Expired Slot Error Rate')['status']);
    }

    public function test_a_failing_test_raises_an_error_rate_above_its_limit(): void
    {
        $tests = [$this->test('failed', ['TC-005']), $this->test('passed', ['TC-006'])];

        $metric = $this->metric((new QualityReport)->build(['tests' => $tests], null, null), 'Expired Slot Error Rate');

        $this->assertSame('50%', $metric['value']);
        $this->assertSame('not_met', $metric['status']);
    }

    public function test_metrics_without_any_test_say_no_data_and_never_pass_by_default(): void
    {
        $report = (new QualityReport)->build(null, null, null);

        foreach ($report['metrics'] as $metric) {
            $this->assertSame('no_data', $metric['status'], $metric['name']);
            $this->assertNull($metric['value']);
        }
        $this->assertFalse($report['has_results']);
    }

    public function test_the_feedback_score_metric_uses_the_live_average(): void
    {
        $this->assertSame('met', $this->metric((new QualityReport)->build(null, null, 2.5), 'User Feedback Score')['status']);
        $this->assertSame('not_met', $this->metric((new QualityReport)->build(null, null, 2.49), 'User Feedback Score')['status']);
        $this->assertSame('2.4 / 3', $this->metric((new QualityReport)->build(null, null, 2.4), 'User Feedback Score')['value']);
    }

    public function test_load_test_metrics_use_the_slowest_page_and_count_every_failure(): void
    {
        $load = ['scenarios' => [
            ['mean_ms' => 40.0, 'failed' => 0, 'non_2xx' => 0],
            ['mean_ms' => 1800.0, 'failed' => 1, 'non_2xx' => 2],
        ]];

        $report = (new QualityReport)->build(null, $load, null);

        $this->assertSame('1800 ms', $this->metric($report, 'Slowest mean response under load')['value']);
        $this->assertSame('met', $this->metric($report, 'Slowest mean response under load')['status']);
        $this->assertSame('3', $this->metric($report, 'Failed requests under load')['value']);
        $this->assertSame('not_met', $this->metric($report, 'Failed requests under load')['status']);
    }

    // Test cases and requirement coverage

    public function test_a_test_case_fails_when_any_of_its_tests_fails_and_has_no_status_when_nothing_ran(): void
    {
        $tests = [
            $this->test('passed', ['TC-001', 'SRS-REQ-301']),
            $this->test('passed', ['TC-002']),
            $this->test('failed', ['TC-002']),
        ];

        $cases = collect((new QualityReport)->build(['tests' => $tests], null, null)['test_cases'])->keyBy('id');

        $this->assertSame('pass', $cases['TC-001']['status']);
        $this->assertSame(['SRS-REQ-301'], $cases['TC-001']['requirements']);
        $this->assertSame('fail', $cases['TC-002']['status']);
        $this->assertSame(2, $cases['TC-002']['tests']);
        $this->assertSame('none', $cases['TC-003']['status']);
        $this->assertCount(13, $cases);
    }

    public function test_every_requirement_lists_the_test_cases_that_cover_it(): void
    {
        $tests = [$this->test('passed', ['TC-005', 'SRS-REQ-309']), $this->test('passed', ['TC-007', 'SRS-REQ-309'])];

        $requirements = collect((new QualityReport)->build(['tests' => $tests], null, null)['requirements'])->keyBy('id');

        $this->assertCount(12, $requirements);
        $this->assertSame(['TC-005', 'TC-007'], $requirements['SRS-REQ-309']['cases']);
        $this->assertSame('pass', $requirements['SRS-REQ-309']['status']);
        $this->assertSame('none', $requirements['SRS-REQ-301']['status']);
    }

    // Reading the saved files

    public function test_saved_files_are_read_and_missing_or_broken_files_give_null(): void
    {
        $quality = new QualityReport($this->folder);
        $this->assertNull($quality->results());
        $this->assertNull($quality->loadTest());

        File::put($this->folder.'/results.json', '{"tests": []}');
        File::put($this->folder.'/load-test.json', 'not json');

        $this->assertSame(['tests' => []], $quality->results());
        $this->assertNull($quality->loadTest());
    }

    // The page

    public function test_only_admin_can_open_the_quality_page(): void
    {
        $this->get('/admin/quality')->assertRedirect('/login');

        foreach ([Role::Patient, Role::Doctor] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))->get('/admin/quality')->assertForbidden();
        }

        $this->actingAs(User::factory()->create(['role' => Role::Admin]))->get('/admin/quality')->assertOk();
    }

    public function test_the_page_explains_how_to_get_results_when_none_are_saved(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Admin]))->get('/admin/quality')
            ->assertSee('No results have been saved yet')
            ->assertSee('php artisan quality:report')
            ->assertSee('No data');
    }

    public function test_the_page_shows_saved_results_metrics_and_the_load_test(): void
    {
        File::put($this->folder.'/results.json', json_encode([
            'generated_at' => now()->toIso8601String(),
            'environment' => ['php' => '8.2.0', 'laravel' => '12', 'database' => 'sqlite', 'commit' => 'abc1234'],
            'tests' => [
                $this->test('passed', ['TC-001', 'SRS-REQ-301']),
                $this->test('failed', ['TC-002', 'SRS-REQ-302']),
            ],
            'full_suite' => ['total' => 300, 'passed' => 299, 'failed' => 1],
        ]));
        File::put($this->folder.'/load-test.json', json_encode([
            'generated_at' => now()->toIso8601String(),
            'tool' => 'ApacheBench (ab -n 100 -c 10)',
            'server' => 'Single-process PHP development server',
            'data' => ['users' => 3, 'appointments' => 5, 'roster_slots' => 40, 'medical_records' => 200],
            'scenarios' => [['label' => 'Doctor: medical record list', 'path' => '/doctor/records', 'requests' => 100, 'concurrency' => 10, 'failed' => 0, 'non_2xx' => 0, 'mean_ms' => 42.5, 'requests_per_second' => 235.1, 'p50_ms' => 40, 'p95_ms' => 60, 'max_ms' => 71]],
        ]));
        Feedback::factory()->count(2)->create(['rating' => 3]);

        $this->actingAs(User::factory()->create(['role' => Role::Admin]))->get('/admin/quality')
            ->assertOk()
            ->assertDontSee('No results have been saved yet')
            ->assertSee('1 / 2')              // quality tests passed / total
            ->assertSee('299 / 300')          // whole suite
            ->assertSee('commit abc1234')
            ->assertSee('TC-001')
            ->assertSee('Task Completion Rate')
            ->assertSee('Not met')
            ->assertSee('3 / 3')              // live feedback score
            ->assertSee('Doctor: medical record list')
            ->assertSee('42.5')
            ->assertSee('200 medical records')
            ->assertSee('Deviations and limits')
            ->assertSee('The report asks for two delete confirmations');
    }

    public function test_the_page_is_in_the_admin_menu_only(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Admin]))->get('/admin/dashboard')
            ->assertSee(route('admin.quality.index'), false);
        $this->actingAs(User::factory()->create())->get('/patient/dashboard')
            ->assertDontSee(route('admin.quality.index'), false);
    }

    public function test_every_test_case_in_the_report_has_at_least_one_automated_test(): void
    {
        // Reads the real test classes, so a TC that loses its test shows up here.
        $xml = '<testsuite>';
        foreach (['Usability', 'Accuracy', 'DataIntegrity', 'Security'] as $class) {
            $reflection = new \ReflectionClass("Tests\\Quality\\Module3\\{$class}Test");

            foreach ($reflection->getMethods() as $method) {
                if (str_starts_with($method->name, 'test_') && $method->class === $reflection->name) {
                    $xml .= '<testcase name="'.$method->name.'" class="'.$reflection->name.'" time="0"/>';
                }
            }
        }
        $tests = (new JunitParser)->parse($xml.'</testsuite>');

        $cases = collect((new QualityReport)->build(['tests' => $tests], null, null)['test_cases']);

        $this->assertSame([], $cases->where('status', 'none')->pluck('id')->all(), 'These test cases have no automated test.');
        $this->assertSame([], collect((new QualityReport)->build(['tests' => $tests], null, null)['requirements'])->where('status', 'none')->pluck('id')->all(), 'These requirements are not covered.');
    }
}
