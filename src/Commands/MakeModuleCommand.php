<?php

declare(strict_types=1);

namespace Mqondisi\ModuleGenerator\Commands;

use Illuminate\Console\Command;
use Mqondisi\ModuleGenerator\Contracts\ResolvesModuleGenerationSpec;
use Mqondisi\ModuleGenerator\Contracts\ScaffoldsModuleFiles;
use Mqondisi\ModuleGenerator\Generators\FileWriteOutcome;
use Mqondisi\ModuleGenerator\Generators\GenerationReport;

/**
 * Artisan entrypoint: `php artisan make:module {name}` with optional feature flags.
 *
 * Examples (Laravel 10 / 11):
 *
 *   php artisan make:module Customer --api
 *   php artisan make:module Customer --inertia
 *   php artisan make:module Customer --tenant
 *   php artisan make:module Customer --with-dao
 *   php artisan make:module Customer --api --tenant --with-dao
 */
class MakeModuleCommand extends Command
{
    protected $signature = 'make:module {name : The module name (e.g. Customer, blog-post)}
        {--api : Generate JSON API controller under Http/Controllers/Api}
        {--inertia : Generate Inertia web controller and Vue page stub}
        {--tenant : Add team_id migration column, model scope, and tenant docblocks}
        {--with-dao : Generate App\\DAO layer; repository depends on DAO interface only}
        {--force : Overwrite existing files (destructive; review diffs before deploying)}';

    protected $description = 'Scaffold model, migration, repository, DTO, optional DAO, and controller(s); skips existing files unless --force';

    public function __construct(
        private readonly ResolvesModuleGenerationSpec $naming,
        private readonly ScaffoldsModuleFiles $scaffolder,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $spec = $this->naming->resolve(
            (string) $this->argument('name'),
            (bool) $this->option('api'),
            (bool) $this->option('inertia'),
            (bool) $this->option('tenant'),
            (bool) $this->option('with-dao'),
            (bool) $this->option('force'),
        );

        $report = new GenerationReport();
        $this->scaffolder->generate($spec, $report);

        $this->renderReport($report);

        return self::SUCCESS;
    }

    private function renderReport(GenerationReport $report): void
    {
        foreach ($report->entries() as $entry) {
            $prefix = match ($entry['outcome']) {
                FileWriteOutcome::Created => '✔ Created:   ',
                FileWriteOutcome::Skipped => '⚠ Skipped:   ',
                FileWriteOutcome::Overwritten => '♻ Overwritten: ',
            };
            $line = $prefix.$entry['path'];
            if ($entry['note'] !== null) {
                $line .= ' — '.$entry['note'];
            }
            $this->line($line);
        }

        $counts = $report->counts();
        $this->newLine();
        $this->info(sprintf(
            'Summary: %d created, %d skipped, %d overwritten. Existing files are never replaced without --force.',
            $counts['created'],
            $counts['skipped'],
            $counts['overwritten']
        ));
    }
}
