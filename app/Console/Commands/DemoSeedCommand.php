<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class DemoSeedCommand extends Command
{
    protected $signature = 'demo:seed';

    protected $description = 'Add the demo accounts and services, but only when the database has no users yet';

    public function handle(): int
    {
        // Safe to run on every start: it never adds duplicates and never touches real data.
        if (User::query()->exists()) {
            $this->info('Skipped. The database already has users.');

            return self::SUCCESS;
        }

        $this->call('db:seed', ['--force' => true]);

        return self::SUCCESS;
    }
}
