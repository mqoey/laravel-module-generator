<?php

declare(strict_types=1);

namespace Mqondisi\ModuleGenerator\Generators;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Mqondisi\ModuleGenerator\Contracts\ScaffoldsModuleFiles;
use Mqondisi\ModuleGenerator\ModuleGenerationSpec;

/**
 * Orchestrates stub rendering and filesystem writes for a single module generation run.
 *
 * Flags on {@see ModuleGenerationSpec} are combinable (e.g. `--api --tenant --with-dao`).
 * When both `--api` and `--inertia` are passed, an API controller and a web Inertia controller are generated.
 */
final class ModuleGenerator implements ScaffoldsModuleFiles
{
    public function __construct(
        private readonly Application $application,
        private readonly Filesystem $filesystem,
        private readonly StubRenderer $stubRenderer,
        private readonly StubReplacementBuilder $replacementBuilder,
        private readonly RepositoryServiceProviderRegistrar $repositoryServiceProviderRegistrar,
    ) {
    }

    public function generate(ModuleGenerationSpec $spec, GenerationReport $report): void
    {
        $basePath = $this->application->basePath();
        $locator = new ModuleArtifactLocator($basePath, $spec);
        $replacements = $this->replacementBuilder->build($spec);

        $this->writeFromStub($basePath, $locator->migrationAbsolutePath(), 'migration.stub', $replacements, $spec->force, $report);
        $this->writeFromStub($basePath, $locator->modelAbsolutePath(), 'model.stub', $replacements, $spec->force, $report);

        if ($spec->withDao && $spec->daoInterfaceName !== null && $spec->daoName !== null) {
            $this->writeFromStub($basePath, $locator->daoInterfaceAbsolutePath(), 'dao-interface.stub', $replacements, $spec->force, $report);
            $this->writeFromStub($basePath, $locator->daoImplementationAbsolutePath(), 'dao.stub', $replacements, $spec->force, $report);
        }

        $this->writeFromStub($basePath, $locator->repositoryInterfaceAbsolutePath(), 'interface.stub', $replacements, $spec->force, $report);

        if (! $spec->withDao) {
            $this->writeFromStub($basePath, $locator->baseRepositoryAbsolutePath(), 'base-repository.stub', [], $spec->force, $report);
        }

        $repositoryStub = $spec->withDao ? 'repository-dao.stub' : 'repository.stub';
        $this->writeFromStub($basePath, $locator->repositoryImplementationAbsolutePath(), $repositoryStub, $replacements, $spec->force, $report);

        $this->writeFromStub($basePath, $locator->dtoAbsolutePath(), 'dto.stub', $replacements, $spec->force, $report);

        $this->writeControllers($basePath, $locator, $replacements, $spec, $report);

        if ($spec->inertia) {
            $pageStub = match ($spec->inertiaStack) {
                'react' => 'react-page.stub',
                'svelte' => 'svelte-page.stub',
                default => 'vue-page.stub',
            };
            $this->writeFromStub($basePath, $locator->inertiaPageAbsolutePath(), $pageStub, $replacements, $spec->force, $report);
        }

        $this->repositoryServiceProviderRegistrar->register($basePath, $spec, $report);
    }

    /**
     * @param  array<string, string>  $replacements
     */
    private function writeControllers(
        string $basePath,
        ModuleArtifactLocator $locator,
        array $replacements,
        ModuleGenerationSpec $spec,
        GenerationReport $report,
    ): void {
        if ($spec->api) {
            $this->writeFromStub($basePath, $locator->apiControllerAbsolutePath(), 'controller.api.stub', $replacements, $spec->force, $report);
        }

        if ($spec->inertia) {
            $this->writeFromStub($basePath, $locator->inertiaControllerAbsolutePath(), 'controller.inertia.stub', $replacements, $spec->force, $report);
        }

        if (! $spec->api && ! $spec->inertia) {
            $this->writeFromStub($basePath, $locator->webControllerAbsolutePath(), 'controller.web.stub', $replacements, $spec->force, $report);
        }
    }

    /**
     * @param  array<string, string>  $replacements
     */
    private function writeFromStub(
        string $applicationBasePath,
        string $absolutePath,
        string $stubFileName,
        array $replacements,
        bool $force,
        GenerationReport $report,
    ): void {
        $contents = $this->stubRenderer->render($stubFileName, $replacements);
        $this->writePhysicalFile($applicationBasePath, $absolutePath, $contents, $force, $report);
    }

    private function writePhysicalFile(
        string $applicationBasePath,
        string $absolutePath,
        string $contents,
        bool $force,
        GenerationReport $report,
    ): void {
        $relative = $this->relativeProjectPath($applicationBasePath, $absolutePath);
        $existed = $this->filesystem->exists($absolutePath);

        if ($existed && ! $force) {
            $report->record($relative, FileWriteOutcome::Skipped, 'exists (pass --force to replace)');

            return;
        }

        $this->filesystem->ensureDirectoryExists(dirname($absolutePath));
        $this->filesystem->put($absolutePath, $contents);

        if ($existed && $force) {
            $report->record($relative, FileWriteOutcome::Overwritten);
        } else {
            $report->record($relative, FileWriteOutcome::Created);
        }
    }

    private function relativeProjectPath(string $applicationBasePath, string $absolutePath): string
    {
        $base = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $applicationBasePath), DIRECTORY_SEPARATOR);
        $abs = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $absolutePath);

        if (str_starts_with($abs, $base.DIRECTORY_SEPARATOR)) {
            return str_replace(DIRECTORY_SEPARATOR, '/', substr($abs, strlen($base) + 1));
        }

        return str_replace(DIRECTORY_SEPARATOR, '/', $abs);
    }
}
