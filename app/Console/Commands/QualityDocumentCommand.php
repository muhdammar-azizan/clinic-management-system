<?php

namespace App\Console\Commands;

use App\Services\QualityReport;
use App\Support\Quality\MarkdownRenderer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class QualityDocumentCommand extends Command
{
    protected $signature = 'quality:document {--check : Do not write. Fail when QUALITY.md is out of date}';

    protected $description = 'Refresh the generated tables in QUALITY.md from the saved quality results';

    public function handle(QualityReport $quality, MarkdownRenderer $renderer): int
    {
        $path = base_path('QUALITY.md');

        if (! is_file($path)) {
            $this->error('QUALITY.md was not found.');

            return self::FAILURE;
        }

        // The feedback score is left out on purpose. It is read live from one database, so it would
        // differ between machines and make the --check result depend on where it runs.
        $generated = $renderer->render($quality->build($quality->results(), $quality->loadTest(), null));

        try {
            $updated = $renderer->replaceBlock((string) file_get_contents($path), $generated);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage().' Add '.MarkdownRenderer::START.' and '.MarkdownRenderer::END.' to QUALITY.md.');

            return self::FAILURE;
        }

        if ($this->option('check')) {
            if ($updated !== file_get_contents($path)) {
                $this->error('QUALITY.md is out of date. Run php artisan quality:document and commit it.');

                return self::FAILURE;
            }

            $this->info('QUALITY.md is up to date.');

            return self::SUCCESS;
        }

        File::put($path, $updated);
        $this->info('QUALITY.md updated.');

        return self::SUCCESS;
    }
}
