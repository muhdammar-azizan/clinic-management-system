<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\RosterSlot;
use App\Models\User;
use App\Support\Quality\ApacheBenchParser;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;

class QualityLoadTestCommand extends Command
{
    protected $signature = 'quality:load-test
        {--url=http://127.0.0.1:8000 : Address of the running application}
        {--requests=100 : Requests per page (ab -n)}
        {--concurrency=10 : Users at the same time (ab -c)}
        {--password=password : Password of the demo accounts}
        {--ab= : Path to ab (ApacheBench). Found automatically in XAMPP}
        {--note=Single-process PHP development server : Describes the server, shown on the Quality page}';

    protected $description = 'Open the main pages with ApacheBench while signed in, and save the timings for the Quality page';

    public function handle(ApacheBenchParser $parser): int
    {
        $base = rtrim((string) $this->option('url'), '/');
        $ab = $this->findAb();

        if ($ab === null) {
            $this->error('ApacheBench (ab) was not found. Install XAMPP or pass --ab=PATH.');

            return self::FAILURE;
        }

        $this->line("Using {$ab}");
        $this->line("Target {$base}, {$this->option('requests')} requests, {$this->option('concurrency')} at a time");

        $scenarios = [];
        $cookies = [];

        foreach (config('quality.load_scenarios') as $scenario) {
            $this->line("\n{$scenario['label']}");

            $cookies[$scenario['email']] ??= $this->signIn($base, $scenario['email']);

            if ($cookies[$scenario['email']] === null) {
                $this->warn('  Could not sign in. Is the server running and the demo data seeded?');

                continue;
            }

            if (! $this->canOpen($base.$scenario['path'], $cookies[$scenario['email']])) {
                $this->warn('  The page did not answer 200 for this account. Skipped.');

                continue;
            }

            $result = $this->bench($ab, $parser, $base.$scenario['path'], $cookies[$scenario['email']]);

            if ($result === null) {
                $this->warn('  ApacheBench failed.');

                continue;
            }

            $scenarios[] = ['label' => $scenario['label'], 'path' => $scenario['path']] + $result;
            $this->info(sprintf('  mean %s ms, 95%% within %s ms, %s requests/s, %d failed', $result['mean_ms'], $result['p95_ms'], $result['requests_per_second'], $result['failed'] + $result['non_2xx']));
        }

        if ($scenarios === []) {
            $this->error('No page could be measured, so nothing was saved.');

            return self::FAILURE;
        }

        $folder = (string) config('quality.path');
        File::ensureDirectoryExists($folder);
        File::put($folder.DIRECTORY_SEPARATOR.'load-test.json', json_encode([
            'generated_at' => now()->toIso8601String(),
            'tool' => 'ApacheBench (ab -n '.$this->option('requests').' -c '.$this->option('concurrency').')',
            'base_url' => $base,
            'server' => (string) $this->option('note'),
            'data' => [
                'users' => User::count(),
                'appointments' => Appointment::count(),
                'roster_slots' => RosterSlot::count(),
                'medical_records' => MedicalRecord::count(),
            ],
            'scenarios' => $scenarios,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

        $this->info("\nSaved quality/load-test.json with ".count($scenarios).' pages.');

        return self::SUCCESS;
    }

    private function findAb(): ?string
    {
        $candidates = array_filter([
            $this->option('ab'),
            config('quality.ab_path'),
            'C:\\xampp\\apache\\bin\\ab.exe',
        ]);

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        $probe = new Process(['ab', '-V']);
        $probe->run();

        return $probe->isSuccessful() ? 'ab' : null;
    }

    /**
     * Sign in like a browser and return the cookie values ab must send, or null on failure.
     *
     * @return list<string>|null "name=value" pairs
     */
    private function signIn(string $base, string $email): ?array
    {
        $jar = new CookieJar;
        $http = fn () => Http::withOptions(['cookies' => $jar, 'allow_redirects' => false])->timeout(30);

        try {
            $page = $http()->get($base.'/login');

            if (! preg_match('/name="_token"\s+value="([^"]+)"/', $page->body(), $match)) {
                return null;
            }

            $login = $http()->asForm()->post($base.'/login', [
                '_token' => $match[1],
                'email' => $email,
                'password' => (string) $this->option('password'),
            ]);
        } catch (\Throwable) {
            return null;
        }

        $location = (string) $login->header('Location');

        if (! $login->redirect() || str_contains($location, '/login')) {
            return null;
        }

        return array_map(fn (array $cookie) => $cookie['Name'].'='.$cookie['Value'], $jar->toArray());
    }

    /**
     * @param  list<string>  $cookies
     */
    private function canOpen(string $url, array $cookies): bool
    {
        try {
            return Http::withHeaders(['Cookie' => implode('; ', $cookies)])->withOptions(['allow_redirects' => false])->timeout(30)->get($url)->status() === 200;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param  list<string>  $cookies
     */
    private function bench(string $ab, ApacheBenchParser $parser, string $url, array $cookies): ?array
    {
        $arguments = [$ab, '-n', (string) $this->option('requests'), '-c', (string) $this->option('concurrency'), '-l'];

        foreach ($cookies as $cookie) {
            array_push($arguments, '-C', $cookie);
        }

        $process = new Process([...$arguments, $url], null, null, null, 600);
        $process->run();

        try {
            return $parser->parse($process->getOutput());
        } catch (\InvalidArgumentException) {
            return null;
        }
    }
}
