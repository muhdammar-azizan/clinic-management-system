<?php

namespace Tests\Feature;

use App\Services\QualityReport;
use App\Support\Quality\MarkdownRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QualityDocumentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function report(?array $load = null): array
    {
        $tests = [
            ['class' => 'A', 'name' => 'a', 'title' => 'A', 'module' => 3, 'attribute' => 'Usability', 'status' => 'passed', 'time' => 0.1, 'groups' => ['TC-001', 'SRS-REQ-301']],
            ['class' => 'A', 'name' => 'b', 'title' => 'B', 'module' => 3, 'attribute' => 'Usability', 'status' => 'failed', 'time' => 0.1, 'groups' => ['TC-002']],
        ];

        return (new QualityReport)->build([
            'generated_at' => '2026-11-22T10:00:00+08:00',
            'environment' => ['php' => '8.2.31', 'laravel' => '12', 'database' => 'SQLite in memory (tests)', 'commit' => 'abc1234'],
            'tests' => $tests,
        ], $load, 2.4);
    }

    public function test_the_text_has_every_section_and_the_right_numbers(): void
    {
        $text = (new MarkdownRenderer)->render($this->report());

        foreach (['### Summary', '### Metrics, Module 3: Doctor Roster', '### Metrics, Module 4: Medical Records', '### Module 3 test cases', '### Requirement coverage', '### Module 4 checks', '### Load test', '### Deviations and limits'] as $heading) {
            $this->assertStringContainsString($heading, $text);
        }

        $this->assertStringContainsString('| Quality tests passed | 1 of 2 (1 failed) |', $text);
        $this->assertStringContainsString('| Results saved | 2026-11-22 |', $text);
        $this->assertStringContainsString('| Commit | `abc1234` |', $text);
        $this->assertStringContainsString('| TC-001 | Usability | Admin creates a new schedule slot | 301 | 1 | Pass |', $text);
        $this->assertStringContainsString('| TC-002 | Usability | Admin views slots in list view and calendar view | - | 1 | Fail |', $text);
        $this->assertStringContainsString('| Usability | User Feedback Score | >= 2.5 / 3 | 2.4 / 3 | Not met |', $text);
    }

    public function test_a_missing_load_test_says_how_to_run_it(): void
    {
        $this->assertStringContainsString('php artisan quality:load-test', (new MarkdownRenderer)->render($this->report()));
    }

    public function test_load_test_rows_show_the_time_of_one_request(): void
    {
        $load = [
            'tool' => 'ApacheBench (ab -n 100 -c 10)', 'base_url' => 'http://127.0.0.1:8001', 'server' => 'Single-process', 'generated_at' => '2026-11-22T10:00:00+08:00',
            'data' => ['users' => 3, 'appointments' => 30, 'roster_slots' => 180, 'medical_records' => 200],
            'scenarios' => [['label' => 'Doctor: medical record list', 'path' => '/doctor/records', 'requests' => 100, 'concurrency' => 10, 'failed' => 1, 'non_2xx' => 1, 'mean_ms' => 2580.5, 'requests_per_second' => 3.88, 'p50_ms' => 2500, 'p95_ms' => 2845, 'max_ms' => 3000]],
        ];

        $text = (new MarkdownRenderer)->render($this->report($load));

        $this->assertStringContainsString('| Doctor: medical record list | 2580.5 | 2845 | 3.88 | 258 | 2 |', $text);
        $this->assertStringContainsString('200 medical records', $text);
    }

    public function test_without_any_results_the_document_says_so_instead_of_inventing_numbers(): void
    {
        $text = (new MarkdownRenderer)->render((new QualityReport)->build(null, null, null));

        $this->assertStringContainsString('No results have been saved yet', $text);
        $this->assertStringNotContainsString('| Met |', $text);
        $this->assertStringContainsString('No data', $text);
    }

    public function test_a_pipe_in_a_cell_does_not_break_the_table(): void
    {
        $renderer = new MarkdownRenderer;
        $report = $this->report();
        $report['deviations'] = [['Topic', 'a | b']];

        $this->assertStringContainsString('a | b', $renderer->render($report));

        $report['test_cases'][0]['title'] = 'one | two';
        $this->assertStringContainsString('one \\| two', $renderer->render($report));
    }

    public function test_the_same_results_always_give_the_same_text(): void
    {
        $renderer = new MarkdownRenderer;

        $this->assertSame($renderer->render($this->report()), $renderer->render($this->report()));
    }

    public function test_only_the_block_between_the_markers_is_replaced(): void
    {
        $renderer = new MarkdownRenderer;
        $document = "# Title\n\nIntro\n\n".MarkdownRenderer::START."\nold\n".MarkdownRenderer::END."\n\nOutro\n";

        $updated = $renderer->replaceBlock($document, 'NEW TEXT');

        $this->assertStringContainsString("# Title\n\nIntro", $updated);
        $this->assertStringContainsString("NEW TEXT\n\n".MarkdownRenderer::END."\n\nOutro", $updated);
        $this->assertStringNotContainsString('old', $updated);
        $this->assertSame($updated, $renderer->replaceBlock($updated, 'NEW TEXT'), 'Running it twice changes nothing.');
    }

    public function test_a_document_without_markers_is_refused(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new MarkdownRenderer)->replaceBlock('# no markers here', 'x');
    }

    public function test_the_real_document_has_the_markers_and_the_readme_links_to_it(): void
    {
        $document = file_get_contents(base_path('QUALITY.md'));

        $this->assertStringContainsString(MarkdownRenderer::START, $document);
        $this->assertStringContainsString(MarkdownRenderer::END, $document);
        $this->assertStringContainsString('(QUALITY.md)', file_get_contents(base_path('README.md')));
    }

    public function test_every_command_in_the_document_exists(): void
    {
        $document = file_get_contents(base_path('QUALITY.md'));

        preg_match_all('/php artisan ([a-z:\-]+)/', $document, $matches);
        $commands = array_keys(\Illuminate\Support\Facades\Artisan::all());

        foreach (array_unique($matches[1]) as $name) {
            $this->assertContains($name, $commands, "QUALITY.md mentions an unknown command: {$name}");
        }
    }

    public function test_the_document_does_not_name_individual_people(): void
    {
        $document = file_get_contents(base_path('QUALITY.md'));

        foreach (['Fatin', 'Faiq', 'Ameera', 'Ammar', 'Roslina'] as $name) {
            $this->assertStringNotContainsString($name, $document);
        }
    }
}
