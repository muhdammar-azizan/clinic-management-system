<?php

namespace App\Console\Commands;

use App\Support\Quality\JunitParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class QualityReportCommand extends Command
{
    protected $signature = 'quality:report {--all : Also run the whole test suite and record its totals (takes a few minutes)}';

    protected $description = 'Run the Quality test suite and save the results for the Quality page';

    public function handle(JunitParser $parser): int
    {
        $folder = (string) config('quality.path');
        File::ensureDirectoryExists($folder);

        $this->info('Running the Quality test suite...');
        $quality = $this->phpunit(['--testsuite=Quality'], $junit = $folder.DIRECTORY_SEPARATOR.'junit-quality.xml');

        if (! is_file($junit)) {
            $this->error('PHPUnit did not write a result file.');
            $this->line($quality->getErrorOutput() ?: $quality->getOutput());

            return self::FAILURE;
        }

        $tests = $parser->parse((string) file_get_contents($junit));
        File::delete($junit);

        $data = [
            'generated_at' => now()->toIso8601String(),
            'environment' => $this->environment(),
            'tests' => $tests,
        ];

        if ($this->option('all')) {
            $this->info('Running the whole test suite...');
            $all = $this->phpunit([], $junitAll = $folder.DIRECTORY_SEPARATOR.'junit-all.xml');

            if (is_file($junitAll)) {
                $everything = $parser->parse((string) file_get_contents($junitAll), fn () => []);
                File::delete($junitAll);

                $data['full_suite'] = [
                    'total' => count($everything),
                    'passed' => count(array_filter($everything, fn ($t) => $t['status'] === 'passed')),
                    'failed' => count(array_filter($everything, fn ($t) => $t['status'] === 'failed')),
                ];
            }
        }

        File::put($folder.DIRECTORY_SEPARATOR.'results.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

        $passed = count(array_filter($tests, fn ($t) => $t['status'] === 'passed'));
        $failed = count(array_filter($tests, fn ($t) => $t['status'] === 'failed'));
        $this->info("Saved quality/results.json: {$passed} passed, {$failed} failed, ".count($tests).' tests.');

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Run PHPUnit directly. The environment is set here so the tests always use their own
     * in-memory database and can never touch the development database.
     *
     * @param  list<string>  $arguments
     */
    private function phpunit(array $arguments, string $junitPath): Process
    {
        $process = new Process(
            [PHP_BINARY, 'vendor/bin/phpunit', ...$arguments, '--log-junit', $junitPath],
            base_path(),
            $this->testEnvironment(),
            null,
            900,
        );
        $process->run();

        return $process;
    }

    /**
     * The settings phpunit.xml gives the tests (in-memory database, array cache and session, ...).
     * They are passed on explicitly because values already set from .env would otherwise win,
     * and the tests would run against the development settings.
     *
     * @return array<string, string>
     */
    private function testEnvironment(): array
    {
        $settings = ['APP_ENV' => 'testing', 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:'];
        $config = simplexml_load_file(base_path('phpunit.xml'));

        foreach ($config->php->env ?? [] as $env) {
            $settings[(string) $env['name']] = (string) $env['value'];
        }

        return $settings;
    }

    /**
     * @return array<string, string|null>
     */
    private function environment(): array
    {
        $commit = new Process(['git', 'rev-parse', '--short', 'HEAD'], base_path());
        $commit->run();

        return [
            'php' => PHP_VERSION,
            'laravel' => app()->version(),
            'database' => 'SQLite in memory (tests)',
            'commit' => $commit->isSuccessful() ? trim($commit->getOutput()) : null,
        ];
    }
}
