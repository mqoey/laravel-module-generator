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
 * Inertia page stubs: use `--inertia-stack=vue` (default), `react`, or `svelte` with `--inertia`.
 */
class MakeModuleCommand extends Command
{
    protected $signature = 'make:module {name : The module name (e.g. Customer, blog-post)}
        {--api : Generate JSON API controller under Http/Controllers/Api}
        {--inertia : Generate Inertia web controller and a page stub (Vue, React, or Svelte via --inertia-stack)}
        {--inertia-stack=vue : Page stub stack when using --inertia: vue, react, or svelte}
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
        try {
            $spec = $this->naming->resolve(
                (string) $this->argument('name'),
                (bool) $this->option('api'),
                (bool) $this->option('inertia'),
                $this->inertiaStackOption(),
                (bool) $this->option('tenant'),
                (bool) $this->option('with-dao'),
                (bool) $this->option('force'),
            );
        } catch (\InvalidArgumentException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $report = new GenerationReport();
        $this->scaffolder->generate($spec, $report);

        $this->renderReport($report);

        return self::SUCCESS;
    }

    private function inertiaStackOption(): string
    {
        $value = $this->option('inertia-stack');

        return is_string($value) ? $value : 'vue';
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
